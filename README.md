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

* PHP 8.0 or higher.
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

### Loaders
Loaders import definition files which describe the components and timeline of a demo. Loaders are classes that implement the ILoader interface. A JSON model is provided by default, but adding other formats should be trivial. 

