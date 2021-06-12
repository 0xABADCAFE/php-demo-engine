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

A bit of fun. An ASCII/ANSI terminal display with stackable routines, sequenced and controlled from a simple text file.

## Examples
[![First Demo](https://img.youtube.com/vi/koEwVBM4a3U/0.jpg)](https://www.youtube.com/watch?v=koEwVBM4a3U) [![Raytracing](https://img.youtube.com/vi/0NdSchCaqlU/0.jpg)](https://www.youtube.com/watch?v=0NdSchCaqlU)
[![Tunnels](https://img.youtube.com/vi/77Ize7KSG1Y/0.jpg)](https://www.youtube.com/watch?v=77Ize7KSG1Y) [![Audio](https://img.youtube.com/vi/flUID_2WPm8/0.jpg)](https://www.youtube.com/watch?v=flUID_2WPm8)

## Requirements

* PHP 7.4.15 or higher.
    * Lower versions of 7.4 may work but 7.4.3 is know not to due to buggy covariant return support.
* A terminal that supports arbitrary RGB ANSI escape sequences and UTF8 blockmode characters.
    * To check if your terminal is likely to work, download the [test card](./docs/testcard.txt) textfile, cat it in your termimal anc compare it to the expected reference output [image](./docs/testcard.png).
* APlay or Sox Play installed for audio output.
    * APlay is usually installed by default on most Linux distributions. For MacOS, sox play is probably your only option.  
* A sense of humour.
* A disregard for best practise and standards.

### Notes
* Audio is still in development. 
* Some terminals will render RGB colours truncated to 256, e.g. xterm and termux.
* Conversion of pixel data to terminal output can consume significant CPU time and is generally offloaded to a sub process.

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
        // Your magic here...
        // You have access to:
        //    $this->oParameters for the current parameter values
        //    $this->oDisplay for the current render target
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

Any time you add new code, you should run the provided `createclassmap` script. This isn't PSR autoloading.

### Loaders
Loaders import definition files which describe the components and timeline of a demo. Loaders are classes that implement the ILoader interface. A JSON model is provided by default, but adding other formats should be trivial.
