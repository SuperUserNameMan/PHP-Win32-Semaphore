# PHP-Win32-Semaphore
Provides Semaphore to PHP 8.x under Windows.

## WHY ?

1. I'm desperatly trying to implement IPC between multiple instances of PHP because multithreading is no more supported under PHP8 ;
2. What works under Linux does not work under Windows and vice-versa ;
3. The precompiled binaries of PHP 8.0.x and 8.1RC for Windows does not provide any semaphore extension ; (edit : there is the [Sync](https://pecl.php.net/package/sync) extension which provides all I need including a precompiled `.dll` for PHP 8.0, but this `.dll` does not work with current 8.1RC1 )
4. IMO, compiling PHP 8 under Windows is a PITA and I hate working under Windows, so recompiling PHP and its extensions myself is out of question ; (please, bring back MinGW support ! MSYS2 is great now !) ;

## Why ? (short version)

Because.
