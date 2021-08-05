<?php


namespace GoLang\Parser;


class GoLangType
{
    const int        = 'int';
    const int8       = 'int8';
    const int16      = 'int16';
    const int32      = 'int32';
    const int64      = 'int64';
    const uint       = 'uint';
    const uint8      = 'uint8';
    const uint16     = 'uint16';
    const uint32     = 'uint32';
    const uint64     = 'uint64';
    const uintptr    = 'uintptr';
    const float32    = 'float32';
    const float64    = 'float64';
    const complex128 = 'complex128';
    const complex64  = 'complex64';
    const bool       = 'bool';
    const byte       = 'byte';
    const rune       = 'rune';
    const string     = 'string';
    const error      = 'error';

    const All = [
        "int",
        "int8",
        "int16",
        "int32",
        "int64",
        "uint",
        "uint8",
        "uint16",
        "uint32",
        "uint64",
        "uintptr",
        "float32",
        "float64",
        "complex128",
        "complex64",
        "bool",
        "byte",
        "rune",
        "string",
        "error",
    ];
}