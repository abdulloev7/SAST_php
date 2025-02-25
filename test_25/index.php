<?php

class A extends PDO {
}

class B extends A {
}

class C extends B {
}

$obj = new C();

$obj->query($_GET["q"]);
