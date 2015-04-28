# proophessor

CQRS + ES for ZF2

Proophessor combines [prooph/service-bus](https://github.com/prooph/service-bus), [proop/event-store](https://github.com/prooph/event-store) and [prooph/event-sourcing](https://github.com/prooph/event-sourcing) in a single ZF2 module. Goal is to simplify the set up process for a full featured CQRS + ES system.

## Planned Key Facts
- Default configuration to get started in no time
- Transaction handling
  - wrap command dispatch with a transaction
  - link recorded events with command to ease debugging
- Synchronious and asynchronious event dispatching
  - Sync dispatch within the command transaction to make sure that read model is always up to date
  - Async dispatch, the event store will act as a job queue so that multiple worker can pull events from it
- Common command and event objects to ease communication between prooph components and reduce translation overhead
- Apigility support
  - Messagebox endpoint for commands
  - Read access to the event store
- ZF2 developer toolbar integration
  - monitor commands and recorded events
  - replay event stream to a specific version
- Snapshot functionality for aggregates


