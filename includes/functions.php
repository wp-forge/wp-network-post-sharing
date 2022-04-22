<?php
// Automatically load all PHP files in the 'includes/hooks/' directory
$iterator = new RecursiveDirectoryIterator( __DIR__ . '/hooks' );
foreach ( new RecursiveIteratorIterator( $iterator ) as $file ) {
    if ( $file->getExtension() === 'php' ) {
        require $file;
    }
}
