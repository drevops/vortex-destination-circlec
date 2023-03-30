<?php

namespace Drupal\Tests\your_site_theme\Kernel;

/**
 * Class ExampleTest.
 *
 * Example kernel test case class.
 *
 * @group YourSiteTheme
 * @group site:kernel
 *
 * @package Drupal\your_site_theme\Tests
 */
class ExampleTest extends YourSiteThemeKernelTestBase {

  /**
   * Tests addition.
   *
   * @dataProvider dataProviderAdd
   * @group addition
   */
  public function testAdd($a, $b, $expected, $expectExceptionMessage = NULL) {
    if ($expectExceptionMessage) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($expectExceptionMessage);
    }

    // Replace below with a call to your class method.
    $actual = $a + $b;

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testAdd().
   */
  public function dataProviderAdd() {
    return [
      [0, 0, 0],
      [1, 1, 2],
      [3, 1, 4],
    ];
  }

  /**
   * Tests subtraction.
   *
   * @dataProvider dataProviderSubtract
   * @group kernel:subtraction
   */
  public function testSubtract($a, $b, $expected, $expectExceptionMessage = NULL) {
    if ($expectExceptionMessage) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($expectExceptionMessage);
    }

    // Replace below with a call to your class method.
    $actual = $a - $b;

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testSubtract().
   */
  public function dataProviderSubtract() {
    return [
      [0, 0, 0],
      [1, 1, 0],
      [2, 1, 1],
      [3, 1, 2],
    ];
  }

  /**
   * Tests multiplication.
   *
   * @dataProvider dataProviderMultiplication
   * @group multiplication
   * @group skipped
   */
  public function testMultiplication($a, $b, $expected, $expectExceptionMessage = NULL) {
    if ($expectExceptionMessage) {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($expectExceptionMessage);
    }

    // Replace below with a call to your class method.
    $actual = $a * $b;

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for testMultiplication().
   */
  public function dataProviderMultiplication() {
    return [
      [0, 0, 0],
      [1, 1, 1],
      [2, 1, 2],
    ];
  }

}
