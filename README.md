# Phower Escaper

A PHP class which offers a way to escape output and defend from XSS and related vulnerabilities by introducing HTML, CSS and Javascript escaping context.

**Phower\Escaper** is inspired in [Zend's escaper component](https://github.com/zendframework/zend-escaper)
and both attempt to minimize the risks from the second most important [OWASP web security risk](https://www.owasp.org/index.php/Top_10_2010-Main).

## Instalation

This package uses [Composer](https://getcomposer.org/) tool for auto-loading and dependency management.
From your project root folder just run:

    composer require phower/html

## Usage

Simply instantiate your object as usual:

    ```php
    use Phower\Escaper;
    $escaper = new Escaper();

Class constructor supports a argument which allows to specify a given encoding format. 
E.g you can escape code from `iso-8859-1` using:

    ```php
    use Phower\Escaper;
    $escaper = new Escaper('iso-8859-1');

