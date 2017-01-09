Yii 2 Application Runtime Configuration extension Change Log
============================================================

1.0.3 under development
-----------------------

- Bug: Fixed `Item::attributes()` returns incorrect attributes list (klimov-paul)
- Enh #7: `Item::$label` converted into virtual property, allowing its automatic generation from `Item::$id` value (nexen2, klimov-paul)
- Enh #8: Added ability to clear only single item value via `Manager::clearValue()` (klimov-paul)


1.0.2, June 3, 2016
-------------------

- Enh #4: `StoragePhp` now invalidates script file cache performed by 'OPCache' or 'APC' (klimov-paul)


1.0.1, April 25, 2016
---------------------

- Bug #1: Fixed `Manager::composeConfig()` triggers PHP Warning in case `items` contains only single element (klimov-paul)


1.0.0, December 29, 2015
------------------------

- Initial release.
