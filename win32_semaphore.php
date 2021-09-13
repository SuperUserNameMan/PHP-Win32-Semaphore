<?php

/***
	 Precompiled Windows version of PHP 8.0.x and 8.1RC do not provide any Semaphore API extension, which render 
	extensions `shmop` and `sysvshm` unstable for IPC on multicore platforms.

	 Also, IMHO, trying to compile PHP to add mising extensions is a PITA under Windows since support for MinGW 
	was removed. On the other hand, Cygwin can compile `php.exe` but can't compile all the extensions I need.

	 So, ths class tries to solve that by using FFI to link to the Win32 Semaphore API.

	It will also try to replicate the `sysvsem` extension API, with some differences :
	- `sem_get()` permissions are ignored ;
	- `sem_release()` can release a semaphore not owned by the process ;
	- the bugs are not the same.

*/

if 
( 
	PHP_OS_FAMILY == 'Windows' 
	and
	! class_exists( 'Win32Semaphore' )
	and
	extension_loaded( 'FFI' )
)
// then :
{
	class Win32Semaphore 
	{
		// --- static ---

		static $win32 = null ;

		static private function ffi_init()
		{
			self::$win32 = FFI::cdef(''
				.'void* CreateSemaphoreA( void* nullable, long initCount , long maxCount , char* name );'.PHP_EOL
				.'unsigned long WaitForSingleObject( void* handle , unsigned long timeout_ms );'.PHP_EOL
				.'int ReleaseSemaphore( void* semaphoreHandle , long releaseCount , long* previousCount_nullable );'.PHP_EOL
				.'int CloseHandle( void* handle );'.PHP_EOL
			,'kernel32.dll');
		}

		// --- dynamic ---

		private int          $key;
		private string       $name;
		private bool         $auto_release;
		private object|false $semaphore;

		public function get( int $key , int $max_acquire , bool $auto_release ) : Win32Semaphore|false
		{
			if ( is_null( self::$win32 ) ) { self::ffi_init(); }
			
			$this->key = $key ;
			$this->name = "php_$key" ;
			$this->auto_release = $auto_release ;

			$this->semaphore = self::$win32->CreateSemaphoreA( null , $max_acquire , $max_acquire , $this->name );

			return ( $this->semaphore == null ) ? false : $this ;
		}

		public function acquire( bool $non_blocking , int $timeout_ms = 0xFFFFffff ) : bool
		{
			return 
				( $this->semaphore !== false )
				and
				( 0 == self::$win32->WaitForSingleObject( $this->semaphore , $non_blocking ? 0 : $timeout_ms ) )
				;
		}

		public function release() : bool
		{
			return
				( $this->semaphore !== false )
				and
				( 0 != self::$win32->ReleaseSemaphore( $this->semaphore , 1 , null ) )
				;
		}

		public function remove() : bool
		{
			return
				( $this->semaphore !== false )
				and
				( 0 != self::$win32->CloseHandle( $this->semaphore ) )
				and
				( ! ( $this->semaphore = false ) ) //!\ assignement /!\ not comparison
				;
		}
		
		function __destruct()
		{
			if ( $this->auto_release )
			{
				$this->release();
				$this->remove();
			}
		}
	
	}


	if ( ! function_exists( 'sem_get' ) )
	{
		function sem_get( int $key , int $max_acquire = 1 , int $permissions = 0666 , bool $auto_release = true ) : Win32Semaphore|false
		{
			$sem = new Win32Semaphore();
			return $sem->get( $key , $max_acquire , $auto_release );
		}
	}

	if ( ! function_exists( 'sem_acquire' ) )
	{
		function sem_acquire( Win32Semaphore $semaphore , bool $non_blocking = false ) : bool
		{
			return $semaphore->acquire( $non_blocking );
		}
	}

	if ( ! function_exists( 'sem_release' ) )
	{
		function sem_release( Win32Semaphore $semaphore ) : bool
		{
			return $semaphore->release();
		}
	}

	if ( ! function_exists( 'sem_remove' ) )
	{
		function sem_remove( Win32Semaphore $semaphore ) : bool
		{
			return $semaphore->remove();
		}
	}
}

// EOF
