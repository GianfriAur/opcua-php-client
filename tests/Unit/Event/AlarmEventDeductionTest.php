<?php

declare(strict_types=1);

use PhpOpcua\Client\Event\AlarmActivated;
use PhpOpcua\Client\Event\AlarmDeactivated;
use PhpOpcua\Client\Event\AlarmEventReceived;
use PhpOpcua\Client\Event\AlarmSeverityChanged;
use PhpOpcua\Client\Event\LimitAlarmExceeded;
use PhpOpcua\Client\Event\OffNormalAlarmTriggered;
use PhpOpcua\Client\Testing\MockClient;
use PhpOpcua\Client\Types\NodeId;

describe('Alarm Event Classes', function () {

    it('creates AlarmEventReceived with all fields', function () {
        $client = MockClient::create();
        $eventType = NodeId::numeric(0, 2955);
        $time = new DateTimeImmutable();

        $event = new AlarmEventReceived(
            client: $client,
            subscriptionId: 1,
            clientHandle: 2,
            eventFields: [],
            severity: 500,
            sourceName: 'Sensor1',
            message: 'Temperature high',
            eventType: $eventType,
            time: $time,
        );

        expect($event->client)->toBe($client);
        expect($event->subscriptionId)->toBe(1);
        expect($event->clientHandle)->toBe(2);
        expect($event->severity)->toBe(500);
        expect($event->sourceName)->toBe('Sensor1');
        expect($event->message)->toBe('Temperature high');
        expect($event->eventType)->toBe($eventType);
        expect($event->time)->toBe($time);
    });

    it('creates AlarmEventReceived with nullable fields', function () {
        $client = MockClient::create();
        $event = new AlarmEventReceived(
            client: $client,
            subscriptionId: 1,
            clientHandle: 2,
            eventFields: [],
        );

        expect($event->severity)->toBeNull();
        expect($event->sourceName)->toBeNull();
        expect($event->message)->toBeNull();
        expect($event->eventType)->toBeNull();
        expect($event->time)->toBeNull();
    });

    it('creates AlarmActivated with severity and message', function () {
        $client = MockClient::create();
        $event = new AlarmActivated(
            client: $client,
            subscriptionId: 1,
            clientHandle: 2,
            sourceName: 'TempSensor',
            severity: 800,
            message: 'Over threshold',
        );

        expect($event->sourceName)->toBe('TempSensor');
        expect($event->severity)->toBe(800);
        expect($event->message)->toBe('Over threshold');
    });

    it('creates AlarmDeactivated without severity', function () {
        $client = MockClient::create();
        $event = new AlarmDeactivated(
            client: $client,
            subscriptionId: 1,
            clientHandle: 2,
            sourceName: 'TempSensor',
            message: 'Back to normal',
        );

        expect($event->sourceName)->toBe('TempSensor');
        expect($event->message)->toBe('Back to normal');
    });

    it('creates AlarmSeverityChanged with severity value', function () {
        $client = MockClient::create();
        $event = new AlarmSeverityChanged(
            client: $client,
            subscriptionId: 1,
            clientHandle: 2,
            sourceName: 'Pump1',
            severity: 900,
        );

        expect($event->severity)->toBe(900);
        expect($event->sourceName)->toBe('Pump1');
    });

    it('creates LimitAlarmExceeded with limit state', function () {
        $client = MockClient::create();
        $event = new LimitAlarmExceeded(
            client: $client,
            subscriptionId: 1,
            clientHandle: 2,
            sourceName: 'Level',
            limitState: 'HighHigh',
            severity: 1000,
        );

        expect($event->limitState)->toBe('HighHigh');
        expect($event->severity)->toBe(1000);
    });

    it('creates OffNormalAlarmTriggered', function () {
        $client = MockClient::create();
        $event = new OffNormalAlarmTriggered(
            client: $client,
            subscriptionId: 1,
            clientHandle: 2,
            sourceName: 'Switch1',
            severity: 500,
        );

        expect($event->sourceName)->toBe('Switch1');
        expect($event->severity)->toBe(500);
    });

});
