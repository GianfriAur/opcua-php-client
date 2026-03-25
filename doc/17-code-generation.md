# Code Generation

## Overview

The `generate:nodeset` CLI command reads OPC UA NodeSet2.xml files and generates PHP classes — eliminating the need to write NodeId constants, codecs, and enum types by hand.

Generated types integrate directly with the client: after loading a registrar, enum values are auto-cast and structured types are decoded into typed DTOs.

## Generating Code

```bash
php vendor/bin/opcua-cli generate:nodeset path/to/CentrifugalPump.NodeSet2.xml \
  --output=src/Generated/CentrifugalPump/ \
  --namespace=App\\OpcUa\\CentrifugalPump
```

**Options:**

| Option | Default | Description |
|--------|---------|-------------|
| `--output=PATH` | `./generated/` | Output directory |
| `--namespace=NS` | `Generated\\OpcUa` | PHP namespace for generated classes |

No server connection required — works entirely from the local XML file.

## What Gets Generated

From a single NodeSet2.xml file, the generator produces up to 5 types of files:

```
src/Generated/CentrifugalPump/
├── CentrifugalPumpNodeIds.php          # NodeId string constants
├── CentrifugalPumpRegistrar.php        # Registrar (implements GeneratedTypeRegistrar)
├── Enums/
│   ├── OperatingStateEnum.php          # PHP backed enums
│   ├── AlarmCodeEnum.php
│   └── ControlModeEnum.php
├── Types/
│   ├── PumpInstantStatusType.php       # Readonly DTO classes
│   ├── PIDConfigurationType.php
│   └── ...
└── Codecs/
    ├── PumpInstantStatusTypeCodec.php   # ExtensionObjectCodec implementations
    ├── PIDConfigurationTypeCodec.php
    └── ...
```

### NodeId Constants

One class with all node IDs as string constants, ready to use with `read()`, `write()`, `browse()`:

```php
class CentrifugalPumpNodeIds
{
    public const PumpFolder = 'ns=1;i=1000';
    public const Temperature = 'ns=1;i=2001';
    public const PumpStatus = 'ns=1;i=2010';
}
```

### Enums

PHP `BackedEnum` for each OPC UA enumeration type:

```php
enum OperatingStateEnum: int
{
    case Off = 0;
    case Starting = 1;
    case Running = 2;
    case Stopping = 3;
    case Alarm = 4;
    case Maintenance = 5;
}
```

### DTOs

Readonly classes with typed properties for each structured DataType. Enum fields are typed with the generated enum:

```php
readonly class PumpInstantStatusType
{
    public function __construct(
        public float $FlowRate_m3h,
        public float $DischargePressure_bar,
        public float $FluidTemperature_C,
        public OperatingStateEnum $State,
        public AlarmCodeEnum $AlarmCode,
        public float $RunningHours,
        public \DateTimeImmutable $AcquisitionTimestamp,
    ) {}
}
```

### Codecs

`ExtensionObjectCodec` implementations that decode binary data into the DTO and encode it back:

```php
class PumpInstantStatusTypeCodec implements ExtensionObjectCodec
{
    public function decode(BinaryDecoder $decoder): PumpInstantStatusType
    {
        return new PumpInstantStatusType(
            $decoder->readFloat(),
            $decoder->readFloat(),
            // ...
            OperatingStateEnum::from($decoder->readInt32()),
            AlarmCodeEnum::from($decoder->readInt32()),
            // ...
        );
    }
}
```

### Registrar

Implements `GeneratedTypeRegistrar` — registers all codecs and provides enum mappings. Uses NodeId constants instead of raw strings:

```php
class CentrifugalPumpRegistrar implements GeneratedTypeRegistrar
{
    public function registerCodecs(ExtensionObjectRepository $repository): void
    {
        $repository->register(
            NodeId::parse(CentrifugalPumpNodeIds::PumpInstantStatusType),
            new Codecs\PumpInstantStatusTypeCodec(),
        );
        // ...
    }

    public function getEnumMappings(): array
    {
        return [
            CentrifugalPumpNodeIds::OperatingStateEnum => Enums\OperatingStateEnum::class,
            CentrifugalPumpNodeIds::AlarmCodeEnum => Enums\AlarmCodeEnum::class,
        ];
    }
}
```

## Loading Generated Types

Use `loadGeneratedTypes()` to register everything with the client:

```php
use Gianfriaur\OpcuaPhpClient\ClientBuilder;
use App\OpcUa\CentrifugalPump\CentrifugalPumpRegistrar;
use App\OpcUa\CentrifugalPump\CentrifugalPumpNodeIds;
use App\OpcUa\CentrifugalPump\Enums\OperatingStateEnum;

$client = ClientBuilder::create()
    ->loadGeneratedTypes(new CentrifugalPumpRegistrar())
    ->connect('opc.tcp://192.168.1.100:4840');
```

After loading:

- **Enum nodes** return the PHP enum directly instead of raw `int`:

```php
$state = $client->read(CentrifugalPumpNodeIds::PumpStatus)->getValue();
// OperatingStateEnum::Running (not int 2)

if ($state === OperatingStateEnum::Alarm) {
    echo "Pump in alarm state!\n";
}
```

- **Structured types** return the typed DTO with property access:

```php
$snapshot = $client->read(CentrifugalPumpNodeIds::InstantStatus)->getValue();
$snapshot->FlowRate_m3h;            // float 45.2
$snapshot->DischargePressure_bar;    // float 3.8
$snapshot->State;                    // OperatingStateEnum::Running
$snapshot->AcquisitionTimestamp;     // DateTimeImmutable
```

## Loading Multiple NodeSets

Call `loadGeneratedTypes()` multiple times on the builder to stack registrars from different NodeSet files:

```php
$client = ClientBuilder::create()
    ->loadGeneratedTypes(new CentrifugalPumpRegistrar())
    ->loadGeneratedTypes(new RoboticsRegistrar())
    ->loadGeneratedTypes(new DiRegistrar())
    ->connect('opc.tcp://192.168.1.100:4840');
```

Each registrar adds its codecs and enum mappings without affecting the others.

## Backward Compatibility

If you don't call `loadGeneratedTypes()`, everything works exactly as before:

```php
$client = ClientBuilder::create()
    ->connect('opc.tcp://192.168.1.100:4840');

$state = $client->read('ns=1;i=100')->getValue();
// int 2 — no enum casting, no DTO wrapping
```

The generated types are opt-in and have zero impact on existing code.

## Supported NodeSet2.xml Elements

| XML Element | Generated Output |
|-------------|-----------------|
| `<UAObject>` | NodeId constant |
| `<UAVariable>` | NodeId constant |
| `<UAMethod>` | NodeId constant |
| `<UADataType>` without `<Definition>` | NodeId constant |
| `<UADataType>` with enum `<Definition>` (fields have `Value=`) | Enum class |
| `<UADataType>` with struct `<Definition>` (fields have `DataType=`) | DTO + Codec |
| `<Aliases>` | Used internally for type resolution |
| `<References>` with `HasEncoding` | Used to find binary encoding NodeIds |

## Generate from a Live Server

Don't have a NodeSet2.xml file? Dump the address space directly from a running OPC UA server:

```bash
# 1. Dump the server's address space
php vendor/bin/opcua-cli dump:nodeset opc.tcp://192.168.1.100:4840 \
  --output=MyPLC.NodeSet2.xml --namespace=2

# 2. Generate PHP types from the dump
php vendor/bin/opcua-cli generate:nodeset MyPLC.NodeSet2.xml \
  --output=src/Generated/MyPLC/ --namespace=App\\OpcUa\\MyPLC

# 3. Use in your code
```

```php
use Gianfriaur\OpcuaPhpClient\ClientBuilder;
use App\OpcUa\MyPLC\MyPLCRegistrar;
use App\OpcUa\MyPLC\MyPLCNodeIds;

$client = ClientBuilder::create()
    ->loadGeneratedTypes(new MyPLCRegistrar())
    ->connect('opc.tcp://192.168.1.100:4840');

$temp = $client->read(MyPLCNodeIds::Temperature)->getValue();
// float 23.5 — with IDE autocomplete on the NodeId constant
```

The `dump:nodeset` command browses the entire address space, reads all node attributes, discovers structured data types and enumerations, and produces a valid NodeSet2.xml file. See [CLI Tool](15-cli.md) for full options.

## Where to Find NodeSet2.xml Files

- **Device manufacturers**: often ship NodeSet files with their OPC UA server documentation
- **Pre-built package**: [`gianfriaur/opcua-php-client-nodeset`](https://github.com/GianfriAur/opcua-php-client-nodeset) — 51 specs already generated
- **OPC Foundation**: [https://github.com/OPCFoundation/UA-Nodeset](https://github.com/OPCFoundation/UA-Nodeset) — all official companion specifications
- **Live server**: use `dump:nodeset` to export from any OPC UA server

