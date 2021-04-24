```

                  ______                            __
          __     /\\\\\\\\_                        /\\\
         /\\\  /\\\//////\\\_                      \/\\\
       /\\\//  \///     \//\\\    ________       ___\/\\\         _______
     /\\\//               /\\\   /\\\\\\\\\_    /\\\\\\\\\       /\\\\\\\\_
   /\\\//_              /\\\\/   /\\\/////\\\   /\\\////\\\     /\\\/////\\\
   \////\\\ __          /\\\/    \/\\\   \/\\\  \/\\\  \/\\\    /\\\\\\\\\\\
       \////\\\ __      \///_     \/\\\___\/\\\  \/\\\__\/\\\   \//\\\//////_
           \////\\\       /\\\     \/\\\\\\\\\\   \//\\\\\\\\\    \//\\\\\\\\\
               \///       \///      \/\\\//////     \/////////      \/////////
                                     \/\\\
                                      \///

                        /P(?:ointless|ortable|HP) Demo Engine/

```
# PDE: The Pointless|Portable|PHP Demo Engine

A bit of fun. A very simple ASCII Display with some routines.

## Requirements

* PHP 7.4.15 or higher.
    * Lower versions of 7.4 may work but 7.4.3 is know not to due to buggy covariant return support.
* A terminal that supports arbitrary RGB ANSI escape sequences and UTF8 blockmode characters.
    * To check if your terminal is likely to work, download the [test card](./docs/testcard.txt) textfile, cat it in your termimal anc compare it to the expected reference output [image](./docs/testcard.png).
* A sense of humour.
* A disregard for best practise and standards.

## Usage

### Natively
Execute `./display <path to json file>`

* If no path is given, the inbuilt demo file is executed.

### Via Docker
```shell
$ docker build -t php-demo-engine .

$ docker run php-demo-engine php display
```

## Structure

### Displays
Displays provide the render target. For now this is limited to basic ASCII output.

### Routines
Routines provide the effects. Routines are classes that implement the IRoutine interface which mandates how they are created, rendered and parameterised. Routines can be stacked, meaning more than one routine is rendered per frame, in a given priority order.

#### Implementing a new Routine
Pretty simples. Implement the `IRoutine` interface directly or extend the `Routine\Base` class which handles a lot of the lower level drudgery already:
```php

namespace ABadCafe\PDE\Routine;
use ABadCafe\PDE;

/**
 * MyRoutine. Does something cool. Probably.
 */
class MyRoutine extends Base {

    const DEFAULT_PARAMETERS = [
        // These are the parameter names that can be set from the demo file and their initial value/types.
        // Define these as you need them. They will populate a member oParameters tuple at runtime.
        'iMyIntParameter'    => 0,
        'fMyFloatParamter'   => 1.5,
        'sMyStringParameter' => 'pde',
        'aMyArrayParameter'  => []
    ];

    /**
     * @inheritDoc
     *
     * Whenever the display changes, this is invoked. It is always called before render() in any given frame.
     */
    public function setDisplay(PDE\IDisplay $oDisplay) : self {
        // Be prepared here to check if this display is suitable for your routine.
        $this->oDisplay = $oDisplay;
        return $this;
    }

    /**
     * @inheritDoc
     *
     * Do your thing. It is expected that your routine will render a single frame. The parameters provide the
     * current frame number and the current time in seconds since the demo began execution.
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        // You need to check if you can render right now...
        if ($this->canRender($iFrameNumber, $fTimeIndex)) {
            // Your magic here...
            // You have access to:
            //    $this->oParameters for the current parameter values
            //    $this->oDisplay for the current render target
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange() {
        // This is called after the an update event in the demo file if any of our parameters change.
    }
}
```
Once your routine is implemented, it must be added to the `Routine\Factory` types array so that it can be found and instantiated by the loader:

```php
namespace ABadCafe\PDE\Routine;

class Factory {

    const TYPES = [
        'MyRoutine' => MyRoutine::class,
    ];

    // ... snip
}
```

### Loaders
Loaders import definition files which describe the components and timeline of a demo. Loaders are classes that implement the ILoader interface. A JSON model is provided by default, but adding other formats should be trivial.
