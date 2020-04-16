# Developers - Customize imports

## Use Events

* Before each Task launch in Pipeline an event `BeforeTaskEvent` is dispatched.
* After each Task launch in Pipeline an event `AfterTaskEvent` is dispatched.

These events have two functions :
* `getTask()` : return the Task class name
* `getPayload()` : return the current Payload class name

The Event can modify the Payload which will then be used.

## Override

You can also overload Class with all the facilities of Symfony.

---

Previous step: [Advanced configuration](CONFIGURE_DETAIL.md)

Next step: [Launch import](LAUNCH.md)
