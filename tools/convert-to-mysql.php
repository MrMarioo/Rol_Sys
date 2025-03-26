<?php

// USAGE: php convert-to-mysql.php test.sql

function convertToMySQL($fileName)
{
    $input = file_get_contents($fileName);

    // Remove legacy settings and unsupported features
    $output = preg_replace('/SET\s+\w+\s*=\s*\w+;/', '', $input);
    $output = preg_replace(
        '/\s*IN\s+DICTIONARY\s+USING\s+\'[^\']+\'/',
        '',
        $output,
    );
    $output = preg_replace('/\s*DCOMPRESS\s*/', '', $output);
    $output = preg_replace('/\s*PAGESIZE\s*=\s*\d+/', '', $output);
    $output = preg_replace('/\s*LINKDUP\s*=\s*\d+/', '', $output);

    // Remove comments
    $output = preg_replace('/--.*\n/', '', $output);

    // remove square brackets
    $output = str_replace('[', '', $output);
    $output = str_replace(']', '', $output);

    // Add unique indexes
    $output = preg_replace(
        '/CREATE\s+UNIQUE\s+INDEX\s+"(.*?)"\s+USING\s+\d+\s+IN\s+DICTIONARY\s+ON\s+"(.*?)"\s+\(\s*(.*?)\s*\);/',
        'CREATE UNIQUE INDEX $1 ON $2 ($3);',
        $output,
    );

    // Add indexes
    $output = preg_replace(
        '/CREATE\s+INDEX\s+"(.*?)"\s+USING\s+\d+\s+IN\s+DICTIONARY\s+ON\s+"(.*?)"\s+\(\s*(.*?)\s*\);/',
        'CREATE INDEX $1 ON $2 ($3);',
        $output,
    );

    // --------------
    // column types mapping
    // --------------

    // replace all CHAR larger than 254 with TEXT
    $output = preg_replace_callback(
        '/\s+CHAR\s*\(\s*(\d+)\s*\)/',
        function ($matches) {
            $length = $matches[1];
            return $length > 254 ? 'TEXT' : "CHAR($length)";
        },
        $output,
    );

    // replace REAL with FLOAT
    $output = preg_replace('/\s+REAL\s*/', ' FLOAT ', $output);

    // replace IDENTITY DEFAULT '<number>' UNIQUE USING <number> with BIGINT AUTO_INCREMENT
    $output = preg_replace(
        '/\s+IDENTITY\s+DEFAULT\s+\'\d+\'\s+UNIQUE\s+USING\s+\d+/',
        ' BIGINT AUTO_INCREMENT PRIMARY KEY',
        $output,
    );

    // Remove legacy types setting
    $output = preg_replace('/SET\s+LEGACYTYPESALLOWED\s+=\s+ON;/', '', $output);

    // Replace double quotes with backticks for table and column names
    $output = str_replace('"', '`', $output);

    // Clean up multiple newlines and spaces
    $output = preg_replace('/\s+/', ' ', $output);
    $output = preg_replace('/\s*;\s*/', ";\n", $output);
    $output = preg_replace('/\s*,\s*/', ",\n\t", $output);
    $output = trim($output);

    return $output;
}

$fileName = $argv[1];
file_put_contents('output.sql', convertToMySQL($fileName));
