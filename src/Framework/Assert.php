<?php
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPUnit\Framework;

use ArrayAccess;
use Countable;
use DOMDocument;
use DOMElement;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\LogicalAnd;
use PHPUnit\Framework\Constraint\ArrayHasKey;
use PHPUnit\Framework\Constraint\ArraySubset;
use PHPUnit\Framework\Constraint\Attribute;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\ClassHasAttribute;
use PHPUnit\Framework\Constraint\ClassHasStaticAttribute;
use PHPUnit\Framework\Constraint\Count;
use PHPUnit\Framework\Constraint\DirectoryExists;
use PHPUnit\Framework\Constraint\FileExists;
use PHPUnit\Framework\Constraint\GreaterThan;
use PHPUnit\Framework\Constraint\IsAnything;
use PHPUnit\Framework\Constraint\IsEmpty;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\Constraint\IsFalse;
use PHPUnit\Framework\Constraint\IsFinite;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\Constraint\IsInfinite;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use PHPUnit_Framework_Constraint_IsJson;
use PHPUnit_Framework_Constraint_IsNan;
use PHPUnit_Framework_Constraint_IsNull;
use PHPUnit_Framework_Constraint_IsReadable;
use PHPUnit_Framework_Constraint_IsTrue;
use PHPUnit_Framework_Constraint_IsType;
use PHPUnit_Framework_Constraint_IsWritable;
use PHPUnit_Framework_Constraint_JsonMatches;
use PHPUnit_Framework_Constraint_LessThan;
use PHPUnit_Framework_Constraint_Not;
use PHPUnit_Framework_Constraint_ObjectHasAttribute;
use PHPUnit_Framework_Constraint_Or;
use PHPUnit_Framework_Constraint_PCREMatch;
use PHPUnit_Framework_Constraint_SameSize;
use PHPUnit_Framework_Constraint_StringContains;
use PHPUnit_Framework_Constraint_StringEndsWith;
use PHPUnit_Framework_Constraint_StringMatches;
use PHPUnit_Framework_Constraint_StringStartsWith;
use PHPUnit_Framework_Constraint_TraversableContains;
use PHPUnit_Framework_Constraint_TraversableContainsOnly;
use PHPUnit_Framework_Constraint_Xor;
use PHPUnit_Util_InvalidArgumentHelper;
use PHPUnit_Util_Type;
use PHPUnit_Util_XML;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;
use Traversable;

/**
 * A set of assertion methods.
 *
 * @since Class available since Release 2.0.0
 */
abstract class Assert
{
    /**
     * @var int
     */
    private static $count = 0;

    /**
     * Asserts that an array has a specified key.
     *
     * @param mixed             $key
     * @param array|ArrayAccess $array
     * @param string            $message
     *
     * @since Method available since Release 3.0.0
     */
    public static function assertArrayHasKey($key, $array, $message = '')
    {
        if (!(is_integer($key) || is_string($key))) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'integer or string'
            );
        }

        if (!(is_array($array) || $array instanceof ArrayAccess)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                2,
                'array or ArrayAccess'
            );
        }

        $constraint = new ArrayHasKey($key);

        static::assertThat($array, $constraint, $message);
    }

    /**
     * Asserts that an array has a specified subset.
     *
     * @param array|ArrayAccess $subset
     * @param array|ArrayAccess $array
     * @param bool              $strict  Check for object identity
     * @param string            $message
     *
     * @since Method available since Release 4.4.0
     */
    public static function assertArraySubset($subset, $array, $strict = false, $message = '')
    {
        if (!is_array($subset)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'array or ArrayAccess'
            );
        }

        if (!is_array($array)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                2,
                'array or ArrayAccess'
            );
        }

        $constraint = new ArraySubset($subset, $strict);

        static::assertThat($array, $constraint, $message);
    }

    /**
     * Asserts that an array does not have a specified key.
     *
     * @param mixed             $key
     * @param array|ArrayAccess $array
     * @param string            $message
     *
     * @since Method available since Release 3.0.0
     */
    public static function assertArrayNotHasKey($key, $array, $message = '')
    {
        if (!(is_integer($key) || is_string($key))) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'integer or string'
            );
        }

        if (!(is_array($array) || $array instanceof ArrayAccess)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                2,
                'array or ArrayAccess'
            );
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new ArrayHasKey($key)
        );

        static::assertThat($array, $constraint, $message);
    }

    /**
     * Asserts that a haystack contains a needle.
     *
     * @param mixed  $needle
     * @param mixed  $haystack
     * @param string $message
     * @param bool   $ignoreCase
     * @param bool   $checkForObjectIdentity
     * @param bool   $checkForNonObjectIdentity
     *
     * @since Method available since Release 2.1.0
     */
    public static function assertContains($needle, $haystack, $message = '', $ignoreCase = false, $checkForObjectIdentity = true, $checkForNonObjectIdentity = false)
    {
        if (is_array($haystack) ||
            is_object($haystack) && $haystack instanceof Traversable) {
            $constraint = new PHPUnit_Framework_Constraint_TraversableContains(
                $needle,
                $checkForObjectIdentity,
                $checkForNonObjectIdentity
            );
        } elseif (is_string($haystack)) {
            if (!is_string($needle)) {
                throw PHPUnit_Util_InvalidArgumentHelper::factory(
                    1,
                    'string'
                );
            }

            $constraint = new PHPUnit_Framework_Constraint_StringContains(
                $needle,
                $ignoreCase
            );
        } else {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                2,
                'array, traversable or string'
            );
        }

        static::assertThat($haystack, $constraint, $message);
    }

    /**
     * Asserts that a haystack that is stored in a static attribute of a class
     * or an attribute of an object contains a needle.
     *
     * @param mixed         $needle
     * @param string        $haystackAttributeName
     * @param string|object $haystackClassOrObject
     * @param string        $message
     * @param bool          $ignoreCase
     * @param bool          $checkForObjectIdentity
     * @param bool          $checkForNonObjectIdentity
     *
     * @since Method available since Release 3.0.0
     */
    public static function assertAttributeContains($needle, $haystackAttributeName, $haystackClassOrObject, $message = '', $ignoreCase = false, $checkForObjectIdentity = true, $checkForNonObjectIdentity = false)
    {
        static::assertContains(
            $needle,
            static::readAttribute($haystackClassOrObject, $haystackAttributeName),
            $message,
            $ignoreCase,
            $checkForObjectIdentity,
            $checkForNonObjectIdentity
        );
    }

    /**
     * Asserts that a haystack does not contain a needle.
     *
     * @param mixed  $needle
     * @param mixed  $haystack
     * @param string $message
     * @param bool   $ignoreCase
     * @param bool   $checkForObjectIdentity
     * @param bool   $checkForNonObjectIdentity
     *
     * @since Method available since Release 2.1.0
     */
    public static function assertNotContains($needle, $haystack, $message = '', $ignoreCase = false, $checkForObjectIdentity = true, $checkForNonObjectIdentity = false)
    {
        if (is_array($haystack) ||
            is_object($haystack) && $haystack instanceof Traversable) {
            $constraint = new PHPUnit_Framework_Constraint_Not(
                new PHPUnit_Framework_Constraint_TraversableContains(
                    $needle,
                    $checkForObjectIdentity,
                    $checkForNonObjectIdentity
                )
            );
        } elseif (is_string($haystack)) {
            if (!is_string($needle)) {
                throw PHPUnit_Util_InvalidArgumentHelper::factory(
                    1,
                    'string'
                );
            }

            $constraint = new PHPUnit_Framework_Constraint_Not(
                new PHPUnit_Framework_Constraint_StringContains(
                    $needle,
                    $ignoreCase
                )
            );
        } else {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                2,
                'array, traversable or string'
            );
        }

        static::assertThat($haystack, $constraint, $message);
    }

    /**
     * Asserts that a haystack that is stored in a static attribute of a class
     * or an attribute of an object does not contain a needle.
     *
     * @param mixed         $needle
     * @param string        $haystackAttributeName
     * @param string|object $haystackClassOrObject
     * @param string        $message
     * @param bool          $ignoreCase
     * @param bool          $checkForObjectIdentity
     * @param bool          $checkForNonObjectIdentity
     *
     * @since Method available since Release 3.0.0
     */
    public static function assertAttributeNotContains($needle, $haystackAttributeName, $haystackClassOrObject, $message = '', $ignoreCase = false, $checkForObjectIdentity = true, $checkForNonObjectIdentity = false)
    {
        static::assertNotContains(
            $needle,
            static::readAttribute($haystackClassOrObject, $haystackAttributeName),
            $message,
            $ignoreCase,
            $checkForObjectIdentity,
            $checkForNonObjectIdentity
        );
    }

    /**
     * Asserts that a haystack contains only values of a given type.
     *
     * @param string $type
     * @param mixed  $haystack
     * @param bool   $isNativeType
     * @param string $message
     *
     * @since Method available since Release 3.1.4
     */
    public static function assertContainsOnly($type, $haystack, $isNativeType = null, $message = '')
    {
        if (!(is_array($haystack) ||
            is_object($haystack) && $haystack instanceof Traversable)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                2,
                'array or traversable'
            );
        }

        if ($isNativeType == null) {
            $isNativeType = PHPUnit_Util_Type::isType($type);
        }

        static::assertThat(
            $haystack,
            new PHPUnit_Framework_Constraint_TraversableContainsOnly(
                $type,
                $isNativeType
            ),
            $message
        );
    }

    /**
     * Asserts that a haystack contains only instances of a given classname
     *
     * @param string            $classname
     * @param array|Traversable $haystack
     * @param string            $message
     */
    public static function assertContainsOnlyInstancesOf($classname, $haystack, $message = '')
    {
        if (!(is_array($haystack) ||
            is_object($haystack) && $haystack instanceof Traversable)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                2,
                'array or traversable'
            );
        }

        static::assertThat(
            $haystack,
            new PHPUnit_Framework_Constraint_TraversableContainsOnly(
                $classname,
                false
            ),
            $message
        );
    }

    /**
     * Asserts that a haystack that is stored in a static attribute of a class
     * or an attribute of an object contains only values of a given type.
     *
     * @param string        $type
     * @param string        $haystackAttributeName
     * @param string|object $haystackClassOrObject
     * @param bool          $isNativeType
     * @param string        $message
     *
     * @since Method available since Release 3.1.4
     */
    public static function assertAttributeContainsOnly($type, $haystackAttributeName, $haystackClassOrObject, $isNativeType = null, $message = '')
    {
        static::assertContainsOnly(
            $type,
            static::readAttribute($haystackClassOrObject, $haystackAttributeName),
            $isNativeType,
            $message
        );
    }

    /**
     * Asserts that a haystack does not contain only values of a given type.
     *
     * @param string $type
     * @param mixed  $haystack
     * @param bool   $isNativeType
     * @param string $message
     *
     * @since Method available since Release 3.1.4
     */
    public static function assertNotContainsOnly($type, $haystack, $isNativeType = null, $message = '')
    {
        if (!(is_array($haystack) ||
            is_object($haystack) && $haystack instanceof Traversable)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                2,
                'array or traversable'
            );
        }

        if ($isNativeType == null) {
            $isNativeType = PHPUnit_Util_Type::isType($type);
        }

        static::assertThat(
            $haystack,
            new PHPUnit_Framework_Constraint_Not(
                new PHPUnit_Framework_Constraint_TraversableContainsOnly(
                    $type,
                    $isNativeType
                )
            ),
            $message
        );
    }

    /**
     * Asserts that a haystack that is stored in a static attribute of a class
     * or an attribute of an object does not contain only values of a given
     * type.
     *
     * @param string        $type
     * @param string        $haystackAttributeName
     * @param string|object $haystackClassOrObject
     * @param bool          $isNativeType
     * @param string        $message
     *
     * @since Method available since Release 3.1.4
     */
    public static function assertAttributeNotContainsOnly($type, $haystackAttributeName, $haystackClassOrObject, $isNativeType = null, $message = '')
    {
        static::assertNotContainsOnly(
            $type,
            static::readAttribute($haystackClassOrObject, $haystackAttributeName),
            $isNativeType,
            $message
        );
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable.
     *
     * @param int    $expectedCount
     * @param mixed  $haystack
     * @param string $message
     */
    public static function assertCount($expectedCount, $haystack, $message = '')
    {
        if (!is_int($expectedCount)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'integer');
        }

        if (!$haystack instanceof Countable &&
            !$haystack instanceof Traversable &&
            !is_array($haystack)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'countable or traversable');
        }

        static::assertThat(
            $haystack,
            new Count($expectedCount),
            $message
        );
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable
     * that is stored in an attribute.
     *
     * @param int           $expectedCount
     * @param string        $haystackAttributeName
     * @param string|object $haystackClassOrObject
     * @param string        $message
     *
     * @since Method available since Release 3.6.0
     */
    public static function assertAttributeCount($expectedCount, $haystackAttributeName, $haystackClassOrObject, $message = '')
    {
        static::assertCount(
            $expectedCount,
            static::readAttribute($haystackClassOrObject, $haystackAttributeName),
            $message
        );
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable.
     *
     * @param int    $expectedCount
     * @param mixed  $haystack
     * @param string $message
     */
    public static function assertNotCount($expectedCount, $haystack, $message = '')
    {
        if (!is_int($expectedCount)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'integer');
        }

        if (!$haystack instanceof Countable &&
            !$haystack instanceof Traversable &&
            !is_array($haystack)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'countable or traversable');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new Count($expectedCount)
        );

        static::assertThat($haystack, $constraint, $message);
    }

    /**
     * Asserts the number of elements of an array, Countable or Traversable
     * that is stored in an attribute.
     *
     * @param int           $expectedCount
     * @param string        $haystackAttributeName
     * @param string|object $haystackClassOrObject
     * @param string        $message
     *
     * @since Method available since Release 3.6.0
     */
    public static function assertAttributeNotCount($expectedCount, $haystackAttributeName, $haystackClassOrObject, $message = '')
    {
        static::assertNotCount(
            $expectedCount,
            static::readAttribute($haystackClassOrObject, $haystackAttributeName),
            $message
        );
    }

    /**
     * Asserts that two variables are equal.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     * @param float  $delta
     * @param int    $maxDepth
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     */
    public static function assertEquals($expected, $actual, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
    {
        $constraint = new IsEqual(
            $expected,
            $delta,
            $maxDepth,
            $canonicalize,
            $ignoreCase
        );

        static::assertThat($actual, $constraint, $message);
    }

    /**
     * Asserts that a variable is equal to an attribute of an object.
     *
     * @param mixed         $expected
     * @param string        $actualAttributeName
     * @param string|object $actualClassOrObject
     * @param string        $message
     * @param float         $delta
     * @param int           $maxDepth
     * @param bool          $canonicalize
     * @param bool          $ignoreCase
     */
    public static function assertAttributeEquals($expected, $actualAttributeName, $actualClassOrObject, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
    {
        static::assertEquals(
            $expected,
            static::readAttribute($actualClassOrObject, $actualAttributeName),
            $message,
            $delta,
            $maxDepth,
            $canonicalize,
            $ignoreCase
        );
    }

    /**
     * Asserts that two variables are not equal.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     * @param float  $delta
     * @param int    $maxDepth
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     *
     * @since Method available since Release 2.3.0
     */
    public static function assertNotEquals($expected, $actual, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
    {
        $constraint = new PHPUnit_Framework_Constraint_Not(
            new IsEqual(
                $expected,
                $delta,
                $maxDepth,
                $canonicalize,
                $ignoreCase
            )
        );

        static::assertThat($actual, $constraint, $message);
    }

    /**
     * Asserts that a variable is not equal to an attribute of an object.
     *
     * @param mixed         $expected
     * @param string        $actualAttributeName
     * @param string|object $actualClassOrObject
     * @param string        $message
     * @param float         $delta
     * @param int           $maxDepth
     * @param bool          $canonicalize
     * @param bool          $ignoreCase
     */
    public static function assertAttributeNotEquals($expected, $actualAttributeName, $actualClassOrObject, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
    {
        static::assertNotEquals(
            $expected,
            static::readAttribute($actualClassOrObject, $actualAttributeName),
            $message,
            $delta,
            $maxDepth,
            $canonicalize,
            $ignoreCase
        );
    }

    /**
     * Asserts that a variable is empty.
     *
     * @param mixed  $actual
     * @param string $message
     *
     * @throws AssertionFailedError
     */
    public static function assertEmpty($actual, $message = '')
    {
        static::assertThat($actual, static::isEmpty(), $message);
    }

    /**
     * Asserts that a static attribute of a class or an attribute of an object
     * is empty.
     *
     * @param string        $haystackAttributeName
     * @param string|object $haystackClassOrObject
     * @param string        $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertAttributeEmpty($haystackAttributeName, $haystackClassOrObject, $message = '')
    {
        static::assertEmpty(
            static::readAttribute($haystackClassOrObject, $haystackAttributeName),
            $message
        );
    }

    /**
     * Asserts that a variable is not empty.
     *
     * @param mixed  $actual
     * @param string $message
     *
     * @throws AssertionFailedError
     */
    public static function assertNotEmpty($actual, $message = '')
    {
        static::assertThat($actual, static::logicalNot(static::isEmpty()), $message);
    }

    /**
     * Asserts that a static attribute of a class or an attribute of an object
     * is not empty.
     *
     * @param string        $haystackAttributeName
     * @param string|object $haystackClassOrObject
     * @param string        $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertAttributeNotEmpty($haystackAttributeName, $haystackClassOrObject, $message = '')
    {
        static::assertNotEmpty(
            static::readAttribute($haystackClassOrObject, $haystackAttributeName),
            $message
        );
    }

    /**
     * Asserts that a value is greater than another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertGreaterThan($expected, $actual, $message = '')
    {
        static::assertThat($actual, static::greaterThan($expected), $message);
    }

    /**
     * Asserts that an attribute is greater than another value.
     *
     * @param mixed         $expected
     * @param string        $actualAttributeName
     * @param string|object $actualClassOrObject
     * @param string        $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertAttributeGreaterThan($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        static::assertGreaterThan(
            $expected,
            static::readAttribute($actualClassOrObject, $actualAttributeName),
            $message
        );
    }

    /**
     * Asserts that a value is greater than or equal to another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertGreaterThanOrEqual($expected, $actual, $message = '')
    {
        static::assertThat(
            $actual,
            static::greaterThanOrEqual($expected),
            $message
        );
    }

    /**
     * Asserts that an attribute is greater than or equal to another value.
     *
     * @param mixed         $expected
     * @param string        $actualAttributeName
     * @param string|object $actualClassOrObject
     * @param string        $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertAttributeGreaterThanOrEqual($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        static::assertGreaterThanOrEqual(
            $expected,
            static::readAttribute($actualClassOrObject, $actualAttributeName),
            $message
        );
    }

    /**
     * Asserts that a value is smaller than another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertLessThan($expected, $actual, $message = '')
    {
        static::assertThat($actual, static::lessThan($expected), $message);
    }

    /**
     * Asserts that an attribute is smaller than another value.
     *
     * @param mixed         $expected
     * @param string        $actualAttributeName
     * @param string|object $actualClassOrObject
     * @param string        $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertAttributeLessThan($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        static::assertLessThan(
            $expected,
            static::readAttribute($actualClassOrObject, $actualAttributeName),
            $message
        );
    }

    /**
     * Asserts that a value is smaller than or equal to another value.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertLessThanOrEqual($expected, $actual, $message = '')
    {
        static::assertThat($actual, static::lessThanOrEqual($expected), $message);
    }

    /**
     * Asserts that an attribute is smaller than or equal to another value.
     *
     * @param mixed         $expected
     * @param string        $actualAttributeName
     * @param string|object $actualClassOrObject
     * @param string        $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertAttributeLessThanOrEqual($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        static::assertLessThanOrEqual(
            $expected,
            static::readAttribute($actualClassOrObject, $actualAttributeName),
            $message
        );
    }

    /**
     * Asserts that the contents of one file is equal to the contents of another
     * file.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     *
     * @since Method available since Release 3.2.14
     */
    public static function assertFileEquals($expected, $actual, $message = '', $canonicalize = false, $ignoreCase = false)
    {
        static::assertFileExists($expected, $message);
        static::assertFileExists($actual, $message);

        static::assertEquals(
            file_get_contents($expected),
            file_get_contents($actual),
            $message,
            0,
            10,
            $canonicalize,
            $ignoreCase
        );
    }

    /**
     * Asserts that the contents of one file is not equal to the contents of
     * another file.
     *
     * @param string $expected
     * @param string $actual
     * @param string $message
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     *
     * @since Method available since Release 3.2.14
     */
    public static function assertFileNotEquals($expected, $actual, $message = '', $canonicalize = false, $ignoreCase = false)
    {
        static::assertFileExists($expected, $message);
        static::assertFileExists($actual, $message);

        static::assertNotEquals(
            file_get_contents($expected),
            file_get_contents($actual),
            $message,
            0,
            10,
            $canonicalize,
            $ignoreCase
        );
    }

    /**
     * Asserts that the contents of a string is equal
     * to the contents of a file.
     *
     * @param string $expectedFile
     * @param string $actualString
     * @param string $message
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     *
     * @since Method available since Release 3.3.0
     */
    public static function assertStringEqualsFile($expectedFile, $actualString, $message = '', $canonicalize = false, $ignoreCase = false)
    {
        static::assertFileExists($expectedFile, $message);

        static::assertEquals(
            file_get_contents($expectedFile),
            $actualString,
            $message,
            0,
            10,
            $canonicalize,
            $ignoreCase
        );
    }

    /**
     * Asserts that the contents of a string is not equal
     * to the contents of a file.
     *
     * @param string $expectedFile
     * @param string $actualString
     * @param string $message
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     *
     * @since Method available since Release 3.3.0
     */
    public static function assertStringNotEqualsFile($expectedFile, $actualString, $message = '', $canonicalize = false, $ignoreCase = false)
    {
        static::assertFileExists($expectedFile, $message);

        static::assertNotEquals(
            file_get_contents($expectedFile),
            $actualString,
            $message,
            0,
            10,
            $canonicalize,
            $ignoreCase
        );
    }

    /**
     * Asserts that a file/dir is readable.
     *
     * @param string $filename
     * @param string $message
     */
    public static function assertIsReadable($filename, $message = '')
    {
        if (!is_string($filename)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_IsReadable;

        static::assertThat($filename, $constraint, $message);
    }

    /**
     * Asserts that a file/dir exists and is not readable.
     *
     * @param string $filename
     * @param string $message
     */
    public static function assertNotIsReadable($filename, $message = '')
    {
        if (!is_string($filename)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new PHPUnit_Framework_Constraint_IsReadable
        );

        static::assertThat($filename, $constraint, $message);
    }

    /**
     * Asserts that a file/dir exists and is writable.
     *
     * @param string $filename
     * @param string $message
     */
    public static function assertIsWritable($filename, $message = '')
    {
        if (!is_string($filename)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_IsWritable;

        static::assertThat($filename, $constraint, $message);
    }

    /**
     * Asserts that a file/dir exists and is not writable.
     *
     * @param string $filename
     * @param string $message
     */
    public static function assertNotIsWritable($filename, $message = '')
    {
        if (!is_string($filename)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new PHPUnit_Framework_Constraint_IsWritable
        );

        static::assertThat($filename, $constraint, $message);
    }

    /**
     * Asserts that a directory exists.
     *
     * @param string $directory
     * @param string $message
     */
    public static function assertDirectoryExists($directory, $message = '')
    {
        if (!is_string($directory)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $constraint = new DirectoryExists;

        static::assertThat($directory, $constraint, $message);
    }

    /**
     * Asserts that a directory does not exist.
     *
     * @param string $directory
     * @param string $message
     */
    public static function assertDirectoryNotExists($directory, $message = '')
    {
        if (!is_string($directory)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new DirectoryExists
        );

        static::assertThat($directory, $constraint, $message);
    }

    /**
     * Asserts that a directory exists and is readable.
     *
     * @param string $directory
     * @param string $message
     */
    public static function assertDirectoryIsReadable($directory, $message = '')
    {
        self::assertDirectoryExists($directory, $message);
        self::assertIsReadable($directory, $message);
    }

    /**
     * Asserts that a directory exists and is not readable.
     *
     * @param string $directory
     * @param string $message
     */
    public static function assertDirectoryNotIsReadable($directory, $message = '')
    {
        self::assertDirectoryExists($directory, $message);
        self::assertNotIsReadable($directory, $message);
    }

    /**
     * Asserts that a directory exists and is writable.
     *
     * @param string $directory
     * @param string $message
     */
    public static function assertDirectoryIsWritable($directory, $message = '')
    {
        self::assertDirectoryExists($directory, $message);
        self::assertIsWritable($directory, $message);
    }

    /**
     * Asserts that a directory exists and is not writable.
     *
     * @param string $directory
     * @param string $message
     */
    public static function assertDirectoryNotIsWritable($directory, $message = '')
    {
        self::assertDirectoryExists($directory, $message);
        self::assertNotIsWritable($directory, $message);
    }

    /**
     * Asserts that a file exists.
     *
     * @param string $filename
     * @param string $message
     *
     * @since Method available since Release 3.0.0
     */
    public static function assertFileExists($filename, $message = '')
    {
        if (!is_string($filename)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $constraint = new FileExists;

        static::assertThat($filename, $constraint, $message);
    }

    /**
     * Asserts that a file does not exist.
     *
     * @param string $filename
     * @param string $message
     *
     * @since Method available since Release 3.0.0
     */
    public static function assertFileNotExists($filename, $message = '')
    {
        if (!is_string($filename)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new FileExists
        );

        static::assertThat($filename, $constraint, $message);
    }

    /**
     * Asserts that a file exists and is readable.
     *
     * @param string $file
     * @param string $message
     */
    public static function assertFileIsReadable($file, $message = '')
    {
        self::assertFileExists($file, $message);
        self::assertIsReadable($file, $message);
    }

    /**
     * Asserts that a file exists and is not readable.
     *
     * @param string $file
     * @param string $message
     */
    public static function assertFileNotIsReadable($file, $message = '')
    {
        self::assertFileExists($file, $message);
        self::assertNotIsReadable($file, $message);
    }

    /**
     * Asserts that a file exists and is writable.
     *
     * @param string $file
     * @param string $message
     */
    public static function assertFileIsWritable($file, $message = '')
    {
        self::assertFileExists($file, $message);
        self::assertIsWritable($file, $message);
    }

    /**
     * Asserts that a file exists and is not writable.
     *
     * @param string $file
     * @param string $message
     */
    public static function assertFileNotIsWritable($file, $message = '')
    {
        self::assertFileExists($file, $message);
        self::assertNotIsWritable($file, $message);
    }

    /**
     * Asserts that a condition is true.
     *
     * @param bool   $condition
     * @param string $message
     *
     * @throws AssertionFailedError
     */
    public static function assertTrue($condition, $message = '')
    {
        static::assertThat($condition, static::isTrue(), $message);
    }

    /**
     * Asserts that a condition is not true.
     *
     * @param bool   $condition
     * @param string $message
     *
     * @throws AssertionFailedError
     */
    public static function assertNotTrue($condition, $message = '')
    {
        static::assertThat($condition, static::logicalNot(static::isTrue()), $message);
    }

    /**
     * Asserts that a condition is false.
     *
     * @param bool   $condition
     * @param string $message
     *
     * @throws AssertionFailedError
     */
    public static function assertFalse($condition, $message = '')
    {
        static::assertThat($condition, static::isFalse(), $message);
    }

    /**
     * Asserts that a condition is not false.
     *
     * @param bool   $condition
     * @param string $message
     *
     * @throws AssertionFailedError
     */
    public static function assertNotFalse($condition, $message = '')
    {
        static::assertThat($condition, static::logicalNot(static::isFalse()), $message);
    }

    /**
     * Asserts that a variable is null.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNull($actual, $message = '')
    {
        static::assertThat($actual, static::isNull(), $message);
    }

    /**
     * Asserts that a variable is not null.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNotNull($actual, $message = '')
    {
        static::assertThat($actual, static::logicalNot(static::isNull()), $message);
    }

    /**
     * Asserts that a variable is finite.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertFinite($actual, $message = '')
    {
        static::assertThat($actual, static::isFinite(), $message);
    }

    /**
     * Asserts that a variable is infinite.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertInfinite($actual, $message = '')
    {
        static::assertThat($actual, static::isInfinite(), $message);
    }

    /**
     * Asserts that a variable is nan.
     *
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNan($actual, $message = '')
    {
        static::assertThat($actual, static::isNan(), $message);
    }

    /**
     * Asserts that a class has a specified attribute.
     *
     * @param string $attributeName
     * @param string $className
     * @param string $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertClassHasAttribute($attributeName, $className, $message = '')
    {
        if (!is_string($attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'valid attribute name');
        }

        if (!is_string($className) || !class_exists($className)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'class name', $className);
        }

        $constraint = new ClassHasAttribute(
            $attributeName
        );

        static::assertThat($className, $constraint, $message);
    }

    /**
     * Asserts that a class does not have a specified attribute.
     *
     * @param string $attributeName
     * @param string $className
     * @param string $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertClassNotHasAttribute($attributeName, $className, $message = '')
    {
        if (!is_string($attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'valid attribute name');
        }

        if (!is_string($className) || !class_exists($className)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'class name', $className);
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new ClassHasAttribute($attributeName)
        );

        static::assertThat($className, $constraint, $message);
    }

    /**
     * Asserts that a class has a specified static attribute.
     *
     * @param string $attributeName
     * @param string $className
     * @param string $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertClassHasStaticAttribute($attributeName, $className, $message = '')
    {
        if (!is_string($attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'valid attribute name');
        }

        if (!is_string($className) || !class_exists($className)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'class name', $className);
        }

        $constraint = new ClassHasStaticAttribute(
            $attributeName
        );

        static::assertThat($className, $constraint, $message);
    }

    /**
     * Asserts that a class does not have a specified static attribute.
     *
     * @param string $attributeName
     * @param string $className
     * @param string $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertClassNotHasStaticAttribute($attributeName, $className, $message = '')
    {
        if (!is_string($attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'valid attribute name');
        }

        if (!is_string($className) || !class_exists($className)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'class name', $className);
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new ClassHasStaticAttribute(
                $attributeName
            )
        );

        static::assertThat($className, $constraint, $message);
    }

    /**
     * Asserts that an object has a specified attribute.
     *
     * @param string $attributeName
     * @param object $object
     * @param string $message
     *
     * @since Method available since Release 3.0.0
     */
    public static function assertObjectHasAttribute($attributeName, $object, $message = '')
    {
        if (!is_string($attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'valid attribute name');
        }

        if (!is_object($object)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'object');
        }

        $constraint = new PHPUnit_Framework_Constraint_ObjectHasAttribute(
            $attributeName
        );

        static::assertThat($object, $constraint, $message);
    }

    /**
     * Asserts that an object does not have a specified attribute.
     *
     * @param string $attributeName
     * @param object $object
     * @param string $message
     *
     * @since Method available since Release 3.0.0
     */
    public static function assertObjectNotHasAttribute($attributeName, $object, $message = '')
    {
        if (!is_string($attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'valid attribute name');
        }

        if (!is_object($object)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'object');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new PHPUnit_Framework_Constraint_ObjectHasAttribute($attributeName)
        );

        static::assertThat($object, $constraint, $message);
    }

    /**
     * Asserts that two variables have the same type and value.
     * Used on objects, it asserts that two variables reference
     * the same object.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertSame($expected, $actual, $message = '')
    {
        if (is_bool($expected) && is_bool($actual)) {
            static::assertEquals($expected, $actual, $message);
        } else {
            $constraint = new IsIdentical(
                $expected
            );

            static::assertThat($actual, $constraint, $message);
        }
    }

    /**
     * Asserts that a variable and an attribute of an object have the same type
     * and value.
     *
     * @param mixed         $expected
     * @param string        $actualAttributeName
     * @param string|object $actualClassOrObject
     * @param string        $message
     */
    public static function assertAttributeSame($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        static::assertSame(
            $expected,
            static::readAttribute($actualClassOrObject, $actualAttributeName),
            $message
        );
    }

    /**
     * Asserts that two variables do not have the same type and value.
     * Used on objects, it asserts that two variables do not reference
     * the same object.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message
     */
    public static function assertNotSame($expected, $actual, $message = '')
    {
        if (is_bool($expected) && is_bool($actual)) {
            static::assertNotEquals($expected, $actual, $message);
        } else {
            $constraint = new PHPUnit_Framework_Constraint_Not(
                new IsIdentical($expected)
            );

            static::assertThat($actual, $constraint, $message);
        }
    }

    /**
     * Asserts that a variable and an attribute of an object do not have the
     * same type and value.
     *
     * @param mixed         $expected
     * @param string        $actualAttributeName
     * @param string|object $actualClassOrObject
     * @param string        $message
     */
    public static function assertAttributeNotSame($expected, $actualAttributeName, $actualClassOrObject, $message = '')
    {
        static::assertNotSame(
            $expected,
            static::readAttribute($actualClassOrObject, $actualAttributeName),
            $message
        );
    }

    /**
     * Asserts that a variable is of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertInstanceOf($expected, $actual, $message = '')
    {
        if (!(is_string($expected) && (class_exists($expected) || interface_exists($expected)))) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'class or interface name');
        }

        $constraint = new IsInstanceOf(
            $expected
        );

        static::assertThat($actual, $constraint, $message);
    }

    /**
     * Asserts that an attribute is of a given type.
     *
     * @param string        $expected
     * @param string        $attributeName
     * @param string|object $classOrObject
     * @param string        $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertAttributeInstanceOf($expected, $attributeName, $classOrObject, $message = '')
    {
        static::assertInstanceOf(
            $expected,
            static::readAttribute($classOrObject, $attributeName),
            $message
        );
    }

    /**
     * Asserts that a variable is not of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertNotInstanceOf($expected, $actual, $message = '')
    {
        if (!(is_string($expected) && (class_exists($expected) || interface_exists($expected)))) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'class or interface name');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new IsInstanceOf($expected)
        );

        static::assertThat($actual, $constraint, $message);
    }

    /**
     * Asserts that an attribute is of a given type.
     *
     * @param string        $expected
     * @param string        $attributeName
     * @param string|object $classOrObject
     * @param string        $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertAttributeNotInstanceOf($expected, $attributeName, $classOrObject, $message = '')
    {
        static::assertNotInstanceOf(
            $expected,
            static::readAttribute($classOrObject, $attributeName),
            $message
        );
    }

    /**
     * Asserts that a variable is of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertInternalType($expected, $actual, $message = '')
    {
        if (!is_string($expected)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_IsType(
            $expected
        );

        static::assertThat($actual, $constraint, $message);
    }

    /**
     * Asserts that an attribute is of a given type.
     *
     * @param string        $expected
     * @param string        $attributeName
     * @param string|object $classOrObject
     * @param string        $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertAttributeInternalType($expected, $attributeName, $classOrObject, $message = '')
    {
        static::assertInternalType(
            $expected,
            static::readAttribute($classOrObject, $attributeName),
            $message
        );
    }

    /**
     * Asserts that a variable is not of a given type.
     *
     * @param string $expected
     * @param mixed  $actual
     * @param string $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertNotInternalType($expected, $actual, $message = '')
    {
        if (!is_string($expected)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new PHPUnit_Framework_Constraint_IsType($expected)
        );

        static::assertThat($actual, $constraint, $message);
    }

    /**
     * Asserts that an attribute is of a given type.
     *
     * @param string        $expected
     * @param string        $attributeName
     * @param string|object $classOrObject
     * @param string        $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertAttributeNotInternalType($expected, $attributeName, $classOrObject, $message = '')
    {
        static::assertNotInternalType(
            $expected,
            static::readAttribute($classOrObject, $attributeName),
            $message
        );
    }

    /**
     * Asserts that a string matches a given regular expression.
     *
     * @param string $pattern
     * @param string $string
     * @param string $message
     */
    public static function assertRegExp($pattern, $string, $message = '')
    {
        if (!is_string($pattern)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!is_string($string)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_PCREMatch($pattern);

        static::assertThat($string, $constraint, $message);
    }

    /**
     * Asserts that a string does not match a given regular expression.
     *
     * @param string $pattern
     * @param string $string
     * @param string $message
     *
     * @since Method available since Release 2.1.0
     */
    public static function assertNotRegExp($pattern, $string, $message = '')
    {
        if (!is_string($pattern)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!is_string($string)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new PHPUnit_Framework_Constraint_PCREMatch($pattern)
        );

        static::assertThat($string, $constraint, $message);
    }

    /**
     * Assert that the size of two arrays (or `Countable` or `Traversable` objects)
     * is the same.
     *
     * @param array|Countable|Traversable $expected
     * @param array|Countable|Traversable $actual
     * @param string                      $message
     */
    public static function assertSameSize($expected, $actual, $message = '')
    {
        if (!$expected instanceof Countable &&
            !$expected instanceof Traversable &&
            !is_array($expected)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'countable or traversable');
        }

        if (!$actual instanceof Countable &&
            !$actual instanceof Traversable &&
            !is_array($actual)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'countable or traversable');
        }

        static::assertThat(
            $actual,
            new PHPUnit_Framework_Constraint_SameSize($expected),
            $message
        );
    }

    /**
     * Assert that the size of two arrays (or `Countable` or `Traversable` objects)
     * is not the same.
     *
     * @param array|Countable|Traversable $expected
     * @param array|Countable|Traversable $actual
     * @param string                      $message
     */
    public static function assertNotSameSize($expected, $actual, $message = '')
    {
        if (!$expected instanceof Countable &&
            !$expected instanceof Traversable &&
            !is_array($expected)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'countable or traversable');
        }

        if (!$actual instanceof Countable &&
            !$actual instanceof Traversable &&
            !is_array($actual)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'countable or traversable');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new PHPUnit_Framework_Constraint_SameSize($expected)
        );

        static::assertThat($actual, $constraint, $message);
    }

    /**
     * Asserts that a string matches a given format string.
     *
     * @param string $format
     * @param string $string
     * @param string $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertStringMatchesFormat($format, $string, $message = '')
    {
        if (!is_string($format)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!is_string($string)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_StringMatches($format);

        static::assertThat($string, $constraint, $message);
    }

    /**
     * Asserts that a string does not match a given format string.
     *
     * @param string $format
     * @param string $string
     * @param string $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertStringNotMatchesFormat($format, $string, $message = '')
    {
        if (!is_string($format)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!is_string($string)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new PHPUnit_Framework_Constraint_StringMatches($format)
        );

        static::assertThat($string, $constraint, $message);
    }

    /**
     * Asserts that a string matches a given format file.
     *
     * @param string $formatFile
     * @param string $string
     * @param string $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertStringMatchesFormatFile($formatFile, $string, $message = '')
    {
        static::assertFileExists($formatFile, $message);

        if (!is_string($string)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_StringMatches(
            file_get_contents($formatFile)
        );

        static::assertThat($string, $constraint, $message);
    }

    /**
     * Asserts that a string does not match a given format string.
     *
     * @param string $formatFile
     * @param string $string
     * @param string $message
     *
     * @since Method available since Release 3.5.0
     */
    public static function assertStringNotMatchesFormatFile($formatFile, $string, $message = '')
    {
        static::assertFileExists($formatFile, $message);

        if (!is_string($string)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new PHPUnit_Framework_Constraint_StringMatches(
                file_get_contents($formatFile)
            )
        );

        static::assertThat($string, $constraint, $message);
    }

    /**
     * Asserts that a string starts with a given prefix.
     *
     * @param string $prefix
     * @param string $string
     * @param string $message
     *
     * @since Method available since Release 3.4.0
     */
    public static function assertStringStartsWith($prefix, $string, $message = '')
    {
        if (!is_string($prefix)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!is_string($string)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_StringStartsWith(
            $prefix
        );

        static::assertThat($string, $constraint, $message);
    }

    /**
     * Asserts that a string starts not with a given prefix.
     *
     * @param string $prefix
     * @param string $string
     * @param string $message
     *
     * @since Method available since Release 3.4.0
     */
    public static function assertStringStartsNotWith($prefix, $string, $message = '')
    {
        if (!is_string($prefix)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!is_string($string)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new PHPUnit_Framework_Constraint_StringStartsWith($prefix)
        );

        static::assertThat($string, $constraint, $message);
    }

    /**
     * Asserts that a string ends with a given suffix.
     *
     * @param string $suffix
     * @param string $string
     * @param string $message
     *
     * @since Method available since Release 3.4.0
     */
    public static function assertStringEndsWith($suffix, $string, $message = '')
    {
        if (!is_string($suffix)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!is_string($string)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_StringEndsWith($suffix);

        static::assertThat($string, $constraint, $message);
    }

    /**
     * Asserts that a string ends not with a given suffix.
     *
     * @param string $suffix
     * @param string $string
     * @param string $message
     *
     * @since Method available since Release 3.4.0
     */
    public static function assertStringEndsNotWith($suffix, $string, $message = '')
    {
        if (!is_string($suffix)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!is_string($string)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        $constraint = new PHPUnit_Framework_Constraint_Not(
            new PHPUnit_Framework_Constraint_StringEndsWith($suffix)
        );

        static::assertThat($string, $constraint, $message);
    }

    /**
     * Asserts that two XML files are equal.
     *
     * @param string $expectedFile
     * @param string $actualFile
     * @param string $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertXmlFileEqualsXmlFile($expectedFile, $actualFile, $message = '')
    {
        $expected = PHPUnit_Util_XML::loadFile($expectedFile);
        $actual   = PHPUnit_Util_XML::loadFile($actualFile);

        static::assertEquals($expected, $actual, $message);
    }

    /**
     * Asserts that two XML files are not equal.
     *
     * @param string $expectedFile
     * @param string $actualFile
     * @param string $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertXmlFileNotEqualsXmlFile($expectedFile, $actualFile, $message = '')
    {
        $expected = PHPUnit_Util_XML::loadFile($expectedFile);
        $actual   = PHPUnit_Util_XML::loadFile($actualFile);

        static::assertNotEquals($expected, $actual, $message);
    }

    /**
     * Asserts that two XML documents are equal.
     *
     * @param string $expectedFile
     * @param string $actualXml
     * @param string $message
     *
     * @since Method available since Release 3.3.0
     */
    public static function assertXmlStringEqualsXmlFile($expectedFile, $actualXml, $message = '')
    {
        $expected = PHPUnit_Util_XML::loadFile($expectedFile);
        $actual   = PHPUnit_Util_XML::load($actualXml);

        static::assertEquals($expected, $actual, $message);
    }

    /**
     * Asserts that two XML documents are not equal.
     *
     * @param string $expectedFile
     * @param string $actualXml
     * @param string $message
     *
     * @since Method available since Release 3.3.0
     */
    public static function assertXmlStringNotEqualsXmlFile($expectedFile, $actualXml, $message = '')
    {
        $expected = PHPUnit_Util_XML::loadFile($expectedFile);
        $actual   = PHPUnit_Util_XML::load($actualXml);

        static::assertNotEquals($expected, $actual, $message);
    }

    /**
     * Asserts that two XML documents are equal.
     *
     * @param string $expectedXml
     * @param string $actualXml
     * @param string $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertXmlStringEqualsXmlString($expectedXml, $actualXml, $message = '')
    {
        $expected = PHPUnit_Util_XML::load($expectedXml);
        $actual   = PHPUnit_Util_XML::load($actualXml);

        static::assertEquals($expected, $actual, $message);
    }

    /**
     * Asserts that two XML documents are not equal.
     *
     * @param string $expectedXml
     * @param string $actualXml
     * @param string $message
     *
     * @since Method available since Release 3.1.0
     */
    public static function assertXmlStringNotEqualsXmlString($expectedXml, $actualXml, $message = '')
    {
        $expected = PHPUnit_Util_XML::load($expectedXml);
        $actual   = PHPUnit_Util_XML::load($actualXml);

        static::assertNotEquals($expected, $actual, $message);
    }

    /**
     * Asserts that a hierarchy of DOMElements matches.
     *
     * @param DOMElement $expectedElement
     * @param DOMElement $actualElement
     * @param bool       $checkAttributes
     * @param string     $message
     *
     * @since Method available since Release 3.3.0
     */
    public static function assertEqualXMLStructure(DOMElement $expectedElement, DOMElement $actualElement, $checkAttributes = false, $message = '')
    {
        $tmp             = new DOMDocument;
        $expectedElement = $tmp->importNode($expectedElement, true);

        $tmp           = new DOMDocument;
        $actualElement = $tmp->importNode($actualElement, true);

        unset($tmp);

        static::assertEquals(
            $expectedElement->tagName,
            $actualElement->tagName,
            $message
        );

        if ($checkAttributes) {
            static::assertEquals(
                $expectedElement->attributes->length,
                $actualElement->attributes->length,
                sprintf(
                    '%s%sNumber of attributes on node "%s" does not match',
                    $message,
                    !empty($message) ? "\n" : '',
                    $expectedElement->tagName
                )
            );

            for ($i = 0; $i < $expectedElement->attributes->length; $i++) {
                $expectedAttribute = $expectedElement->attributes->item($i);
                $actualAttribute   = $actualElement->attributes->getNamedItem(
                    $expectedAttribute->name
                );

                if (!$actualAttribute) {
                    static::fail(
                        sprintf(
                            '%s%sCould not find attribute "%s" on node "%s"',
                            $message,
                            !empty($message) ? "\n" : '',
                            $expectedAttribute->name,
                            $expectedElement->tagName
                        )
                    );
                }
            }
        }

        PHPUnit_Util_XML::removeCharacterDataNodes($expectedElement);
        PHPUnit_Util_XML::removeCharacterDataNodes($actualElement);

        static::assertEquals(
            $expectedElement->childNodes->length,
            $actualElement->childNodes->length,
            sprintf(
                '%s%sNumber of child nodes of "%s" differs',
                $message,
                !empty($message) ? "\n" : '',
                $expectedElement->tagName
            )
        );

        for ($i = 0; $i < $expectedElement->childNodes->length; $i++) {
            static::assertEqualXMLStructure(
                $expectedElement->childNodes->item($i),
                $actualElement->childNodes->item($i),
                $checkAttributes,
                $message
            );
        }
    }

    /**
     * Evaluates a PHPUnit_Framework_Constraint matcher object.
     *
     * @param mixed      $value
     * @param Constraint $constraint
     * @param string     $message
     *
     * @since Method available since Release 3.0.0
     */
    public static function assertThat($value, Constraint $constraint, $message = '')
    {
        self::$count += count($constraint);

        $constraint->evaluate($value, $message);
    }

    /**
     * Asserts that a string is a valid JSON string.
     *
     * @param string $actualJson
     * @param string $message
     *
     * @since Method available since Release 3.7.20
     */
    public static function assertJson($actualJson, $message = '')
    {
        if (!is_string($actualJson)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        static::assertThat($actualJson, static::isJson(), $message);
    }

    /**
     * Asserts that two given JSON encoded objects or arrays are equal.
     *
     * @param string $expectedJson
     * @param string $actualJson
     * @param string $message
     */
    public static function assertJsonStringEqualsJsonString($expectedJson, $actualJson, $message = '')
    {
        static::assertJson($expectedJson, $message);
        static::assertJson($actualJson, $message);

        $expected = json_decode($expectedJson);
        $actual   = json_decode($actualJson);

        static::assertEquals($expected, $actual, $message);
    }

    /**
     * Asserts that two given JSON encoded objects or arrays are not equal.
     *
     * @param string $expectedJson
     * @param string $actualJson
     * @param string $message
     */
    public static function assertJsonStringNotEqualsJsonString($expectedJson, $actualJson, $message = '')
    {
        static::assertJson($expectedJson, $message);
        static::assertJson($actualJson, $message);

        $expected = json_decode($expectedJson);
        $actual   = json_decode($actualJson);

        static::assertNotEquals($expected, $actual, $message);
    }

    /**
     * Asserts that the generated JSON encoded object and the content of the given file are equal.
     *
     * @param string $expectedFile
     * @param string $actualJson
     * @param string $message
     */
    public static function assertJsonStringEqualsJsonFile($expectedFile, $actualJson, $message = '')
    {
        static::assertFileExists($expectedFile, $message);
        $expectedJson = file_get_contents($expectedFile);

        static::assertJson($expectedJson, $message);
        static::assertJson($actualJson, $message);

        // call constraint
        $constraint = new PHPUnit_Framework_Constraint_JsonMatches(
            $expectedJson
        );

        static::assertThat($actualJson, $constraint, $message);
    }

    /**
     * Asserts that the generated JSON encoded object and the content of the given file are not equal.
     *
     * @param string $expectedFile
     * @param string $actualJson
     * @param string $message
     */
    public static function assertJsonStringNotEqualsJsonFile($expectedFile, $actualJson, $message = '')
    {
        static::assertFileExists($expectedFile, $message);
        $expectedJson = file_get_contents($expectedFile);

        static::assertJson($expectedJson, $message);
        static::assertJson($actualJson, $message);

        // call constraint
        $constraint = new PHPUnit_Framework_Constraint_JsonMatches(
            $expectedJson
        );

        static::assertThat($actualJson, new PHPUnit_Framework_Constraint_Not($constraint), $message);
    }

    /**
     * Asserts that two JSON files are equal.
     *
     * @param string $expectedFile
     * @param string $actualFile
     * @param string $message
     */
    public static function assertJsonFileEqualsJsonFile($expectedFile, $actualFile, $message = '')
    {
        static::assertFileExists($expectedFile, $message);
        static::assertFileExists($actualFile, $message);

        $actualJson   = file_get_contents($actualFile);
        $expectedJson = file_get_contents($expectedFile);

        static::assertJson($expectedJson, $message);
        static::assertJson($actualJson, $message);

        // call constraint
        $constraintExpected = new PHPUnit_Framework_Constraint_JsonMatches(
            $expectedJson
        );

        $constraintActual = new PHPUnit_Framework_Constraint_JsonMatches($actualJson);

        static::assertThat($expectedJson, $constraintActual, $message);
        static::assertThat($actualJson, $constraintExpected, $message);
    }

    /**
     * Asserts that two JSON files are not equal.
     *
     * @param string $expectedFile
     * @param string $actualFile
     * @param string $message
     */
    public static function assertJsonFileNotEqualsJsonFile($expectedFile, $actualFile, $message = '')
    {
        static::assertFileExists($expectedFile, $message);
        static::assertFileExists($actualFile, $message);

        $actualJson   = file_get_contents($actualFile);
        $expectedJson = file_get_contents($expectedFile);

        static::assertJson($expectedJson, $message);
        static::assertJson($actualJson, $message);

        // call constraint
        $constraintExpected = new PHPUnit_Framework_Constraint_JsonMatches(
            $expectedJson
        );

        $constraintActual = new PHPUnit_Framework_Constraint_JsonMatches($actualJson);

        static::assertThat($expectedJson, new PHPUnit_Framework_Constraint_Not($constraintActual), $message);
        static::assertThat($actualJson, new PHPUnit_Framework_Constraint_Not($constraintExpected), $message);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_And matcher object.
     *
     * @return LogicalAnd
     *
     * @since Method available since Release 3.0.0
     */
    public static function logicalAnd()
    {
        $constraints = func_get_args();

        $constraint = new LogicalAnd;
        $constraint->setConstraints($constraints);

        return $constraint;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_Or matcher object.
     *
     * @return PHPUnit_Framework_Constraint_Or
     *
     * @since Method available since Release 3.0.0
     */
    public static function logicalOr()
    {
        $constraints = func_get_args();

        $constraint = new PHPUnit_Framework_Constraint_Or;
        $constraint->setConstraints($constraints);

        return $constraint;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_Not matcher object.
     *
     * @param Constraint $constraint
     *
     * @return PHPUnit_Framework_Constraint_Not
     *
     * @since Method available since Release 3.0.0
     */
    public static function logicalNot(Constraint $constraint)
    {
        return new PHPUnit_Framework_Constraint_Not($constraint);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_Xor matcher object.
     *
     * @return PHPUnit_Framework_Constraint_Xor
     *
     * @since Method available since Release 3.0.0
     */
    public static function logicalXor()
    {
        $constraints = func_get_args();

        $constraint = new PHPUnit_Framework_Constraint_Xor;
        $constraint->setConstraints($constraints);

        return $constraint;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsAnything matcher object.
     *
     * @return IsAnything
     *
     * @since Method available since Release 3.0.0
     */
    public static function anything()
    {
        return new IsAnything;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsTrue matcher object.
     *
     * @return PHPUnit_Framework_Constraint_IsTrue
     *
     * @since Method available since Release 3.3.0
     */
    public static function isTrue()
    {
        return new PHPUnit_Framework_Constraint_IsTrue;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_Callback matcher object.
     *
     * @param callable $callback
     *
     * @return Callback
     */
    public static function callback($callback)
    {
        return new Callback($callback);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsFalse matcher object.
     *
     * @return IsFalse
     *
     * @since Method available since Release 3.3.0
     */
    public static function isFalse()
    {
        return new IsFalse;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsJson matcher object.
     *
     * @return PHPUnit_Framework_Constraint_IsJson
     *
     * @since Method available since Release 3.7.20
     */
    public static function isJson()
    {
        return new PHPUnit_Framework_Constraint_IsJson;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsNull matcher object.
     *
     * @return PHPUnit_Framework_Constraint_IsNull
     *
     * @since Method available since Release 3.3.0
     */
    public static function isNull()
    {
        return new PHPUnit_Framework_Constraint_IsNull;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsFinite matcher object.
     *
     * @return IsFinite
     *
     * @since Method available since Release 5.0.0
     */
    public static function isFinite()
    {
        return new IsFinite;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsInfinite matcher object.
     *
     * @return IsInfinite
     *
     * @since Method available since Release 5.0.0
     */
    public static function isInfinite()
    {
        return new IsInfinite;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsNan matcher object.
     *
     * @return PHPUnit_Framework_Constraint_IsNan
     *
     * @since Method available since Release 5.0.0
     */
    public static function isNan()
    {
        return new PHPUnit_Framework_Constraint_IsNan;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_Attribute matcher object.
     *
     * @param Constraint $constraint
     * @param string     $attributeName
     *
     * @return Attribute
     *
     * @since Method available since Release 3.1.0
     */
    public static function attribute(Constraint $constraint, $attributeName)
    {
        return new Attribute(
            $constraint,
            $attributeName
        );
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_TraversableContains matcher
     * object.
     *
     * @param mixed $value
     * @param bool  $checkForObjectIdentity
     * @param bool  $checkForNonObjectIdentity
     *
     * @return PHPUnit_Framework_Constraint_TraversableContains
     *
     * @since Method available since Release 3.0.0
     */
    public static function contains($value, $checkForObjectIdentity = true, $checkForNonObjectIdentity = false)
    {
        return new PHPUnit_Framework_Constraint_TraversableContains($value, $checkForObjectIdentity, $checkForNonObjectIdentity);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_TraversableContainsOnly matcher
     * object.
     *
     * @param string $type
     *
     * @return PHPUnit_Framework_Constraint_TraversableContainsOnly
     *
     * @since Method available since Release 3.1.4
     */
    public static function containsOnly($type)
    {
        return new PHPUnit_Framework_Constraint_TraversableContainsOnly($type);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_TraversableContainsOnly matcher
     * object.
     *
     * @param string $classname
     *
     * @return PHPUnit_Framework_Constraint_TraversableContainsOnly
     */
    public static function containsOnlyInstancesOf($classname)
    {
        return new PHPUnit_Framework_Constraint_TraversableContainsOnly($classname, false);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_ArrayHasKey matcher object.
     *
     * @param mixed $key
     *
     * @return ArrayHasKey
     *
     * @since Method available since Release 3.0.0
     */
    public static function arrayHasKey($key)
    {
        return new ArrayHasKey($key);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsEqual matcher object.
     *
     * @param mixed $value
     * @param float $delta
     * @param int   $maxDepth
     * @param bool  $canonicalize
     * @param bool  $ignoreCase
     *
     * @return IsEqual
     *
     * @since Method available since Release 3.0.0
     */
    public static function equalTo($value, $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
    {
        return new IsEqual(
            $value,
            $delta,
            $maxDepth,
            $canonicalize,
            $ignoreCase
        );
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsEqual matcher object
     * that is wrapped in a PHPUnit_Framework_Constraint_Attribute matcher
     * object.
     *
     * @param string $attributeName
     * @param mixed  $value
     * @param float  $delta
     * @param int    $maxDepth
     * @param bool   $canonicalize
     * @param bool   $ignoreCase
     *
     * @return Attribute
     *
     * @since Method available since Release 3.1.0
     */
    public static function attributeEqualTo($attributeName, $value, $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
    {
        return static::attribute(
            static::equalTo(
                $value,
                $delta,
                $maxDepth,
                $canonicalize,
                $ignoreCase
            ),
            $attributeName
        );
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsEmpty matcher object.
     *
     * @return IsEmpty
     *
     * @since Method available since Release 3.5.0
     */
    public static function isEmpty()
    {
        return new IsEmpty;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsWritable matcher object.
     *
     * @return PHPUnit_Framework_Constraint_IsWritable
     */
    public static function isWritable()
    {
        return new PHPUnit_Framework_Constraint_IsWritable;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsReadable matcher object.
     *
     * @return PHPUnit_Framework_Constraint_IsReadable
     */
    public static function isReadable()
    {
        return new PHPUnit_Framework_Constraint_IsReadable;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_DirectoryExists matcher object.
     *
     * @return DirectoryExists
     */
    public static function directoryExists()
    {
        return new DirectoryExists;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_FileExists matcher object.
     *
     * @return FileExists
     *
     * @since Method available since Release 3.0.0
     */
    public static function fileExists()
    {
        return new FileExists;
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_GreaterThan matcher object.
     *
     * @param mixed $value
     *
     * @return GreaterThan
     *
     * @since Method available since Release 3.0.0
     */
    public static function greaterThan($value)
    {
        return new GreaterThan($value);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_Or matcher object that wraps
     * a PHPUnit_Framework_Constraint_IsEqual and a
     * PHPUnit_Framework_Constraint_GreaterThan matcher object.
     *
     * @param mixed $value
     *
     * @return PHPUnit_Framework_Constraint_Or
     *
     * @since Method available since Release 3.1.0
     */
    public static function greaterThanOrEqual($value)
    {
        return static::logicalOr(
            new IsEqual($value),
            new GreaterThan($value)
        );
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_ClassHasAttribute matcher object.
     *
     * @param string $attributeName
     *
     * @return ClassHasAttribute
     *
     * @since Method available since Release 3.1.0
     */
    public static function classHasAttribute($attributeName)
    {
        return new ClassHasAttribute(
            $attributeName
        );
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_ClassHasStaticAttribute matcher
     * object.
     *
     * @param string $attributeName
     *
     * @return ClassHasStaticAttribute
     *
     * @since Method available since Release 3.1.0
     */
    public static function classHasStaticAttribute($attributeName)
    {
        return new ClassHasStaticAttribute(
            $attributeName
        );
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_ObjectHasAttribute matcher object.
     *
     * @param string $attributeName
     *
     * @return PHPUnit_Framework_Constraint_ObjectHasAttribute
     *
     * @since Method available since Release 3.0.0
     */
    public static function objectHasAttribute($attributeName)
    {
        return new PHPUnit_Framework_Constraint_ObjectHasAttribute(
            $attributeName
        );
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsIdentical matcher object.
     *
     * @param mixed $value
     *
     * @return IsIdentical
     *
     * @since Method available since Release 3.0.0
     */
    public static function identicalTo($value)
    {
        return new IsIdentical($value);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsInstanceOf matcher object.
     *
     * @param string $className
     *
     * @return IsInstanceOf
     *
     * @since Method available since Release 3.0.0
     */
    public static function isInstanceOf($className)
    {
        return new IsInstanceOf($className);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_IsType matcher object.
     *
     * @param string $type
     *
     * @return PHPUnit_Framework_Constraint_IsType
     *
     * @since Method available since Release 3.0.0
     */
    public static function isType($type)
    {
        return new PHPUnit_Framework_Constraint_IsType($type);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_LessThan matcher object.
     *
     * @param mixed $value
     *
     * @return PHPUnit_Framework_Constraint_LessThan
     *
     * @since Method available since Release 3.0.0
     */
    public static function lessThan($value)
    {
        return new PHPUnit_Framework_Constraint_LessThan($value);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_Or matcher object that wraps
     * a PHPUnit_Framework_Constraint_IsEqual and a
     * PHPUnit_Framework_Constraint_LessThan matcher object.
     *
     * @param mixed $value
     *
     * @return PHPUnit_Framework_Constraint_Or
     *
     * @since Method available since Release 3.1.0
     */
    public static function lessThanOrEqual($value)
    {
        return static::logicalOr(
            new IsEqual($value),
            new PHPUnit_Framework_Constraint_LessThan($value)
        );
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_PCREMatch matcher object.
     *
     * @param string $pattern
     *
     * @return PHPUnit_Framework_Constraint_PCREMatch
     *
     * @since Method available since Release 3.0.0
     */
    public static function matchesRegularExpression($pattern)
    {
        return new PHPUnit_Framework_Constraint_PCREMatch($pattern);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_StringMatches matcher object.
     *
     * @param string $string
     *
     * @return PHPUnit_Framework_Constraint_StringMatches
     *
     * @since Method available since Release 3.5.0
     */
    public static function matches($string)
    {
        return new PHPUnit_Framework_Constraint_StringMatches($string);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_StringStartsWith matcher object.
     *
     * @param mixed $prefix
     *
     * @return PHPUnit_Framework_Constraint_StringStartsWith
     *
     * @since Method available since Release 3.4.0
     */
    public static function stringStartsWith($prefix)
    {
        return new PHPUnit_Framework_Constraint_StringStartsWith($prefix);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_StringContains matcher object.
     *
     * @param string $string
     * @param bool   $case
     *
     * @return PHPUnit_Framework_Constraint_StringContains
     *
     * @since Method available since Release 3.0.0
     */
    public static function stringContains($string, $case = true)
    {
        return new PHPUnit_Framework_Constraint_StringContains($string, $case);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_StringEndsWith matcher object.
     *
     * @param mixed $suffix
     *
     * @return PHPUnit_Framework_Constraint_StringEndsWith
     *
     * @since Method available since Release 3.4.0
     */
    public static function stringEndsWith($suffix)
    {
        return new PHPUnit_Framework_Constraint_StringEndsWith($suffix);
    }

    /**
     * Returns a PHPUnit_Framework_Constraint_Count matcher object.
     *
     * @param int $count
     *
     * @return Count
     */
    public static function countOf($count)
    {
        return new Count($count);
    }

    /**
     * Fails a test with the given message.
     *
     * @param string $message
     *
     * @throws AssertionFailedError
     */
    public static function fail($message = '')
    {
        self::$count++;

        throw new AssertionFailedError($message);
    }

    /**
     * Returns the value of an attribute of a class or an object.
     * This also works for attributes that are declared protected or private.
     *
     * @param string|object $classOrObject
     * @param string        $attributeName
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function readAttribute($classOrObject, $attributeName)
    {
        if (!is_string($attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        if (!preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'valid attribute name');
        }

        if (is_string($classOrObject)) {
            if (!class_exists($classOrObject)) {
                throw PHPUnit_Util_InvalidArgumentHelper::factory(
                    1,
                    'class name'
                );
            }

            return static::getStaticAttribute(
                $classOrObject,
                $attributeName
            );
        } elseif (is_object($classOrObject)) {
            return static::getObjectAttribute(
                $classOrObject,
                $attributeName
            );
        } else {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'class name or object'
            );
        }
    }

    /**
     * Returns the value of a static attribute.
     * This also works for attributes that are declared protected or private.
     *
     * @param string $className
     * @param string $attributeName
     *
     * @return mixed
     *
     * @throws Exception
     *
     * @since Method available since Release 4.0.0
     */
    public static function getStaticAttribute($className, $attributeName)
    {
        if (!is_string($className)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
        }

        if (!class_exists($className)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'class name');
        }

        if (!is_string($attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        if (!preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'valid attribute name');
        }

        $class = new ReflectionClass($className);

        while ($class) {
            $attributes = $class->getStaticProperties();

            if (array_key_exists($attributeName, $attributes)) {
                return $attributes[$attributeName];
            }

            $class = $class->getParentClass();
        }

        throw new Exception(
            sprintf(
                'Attribute "%s" not found in class.',
                $attributeName
            )
        );
    }

    /**
     * Returns the value of an object's attribute.
     * This also works for attributes that are declared protected or private.
     *
     * @param object $object
     * @param string $attributeName
     *
     * @return mixed
     *
     * @throws Exception
     *
     * @since Method available since Release 4.0.0
     */
    public static function getObjectAttribute($object, $attributeName)
    {
        if (!is_object($object)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'object');
        }

        if (!is_string($attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
        }

        if (!preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $attributeName)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'valid attribute name');
        }

        try {
            $attribute = new ReflectionProperty($object, $attributeName);
        } catch (ReflectionException $e) {
            $reflector = new ReflectionObject($object);

            while ($reflector = $reflector->getParentClass()) {
                try {
                    $attribute = $reflector->getProperty($attributeName);
                    break;
                } catch (ReflectionException $e) {
                }
            }
        }

        if (isset($attribute)) {
            if (!$attribute || $attribute->isPublic()) {
                return $object->$attributeName;
            }

            $attribute->setAccessible(true);
            $value = $attribute->getValue($object);
            $attribute->setAccessible(false);

            return $value;
        }

        throw new Exception(
            sprintf(
                'Attribute "%s" not found in object.',
                $attributeName
            )
        );
    }

    /**
     * Mark the test as incomplete.
     *
     * @param string $message
     *
     * @throws IncompleteTestError
     *
     * @since Method available since Release 3.0.0
     */
    public static function markTestIncomplete($message = '')
    {
        throw new IncompleteTestError($message);
    }

    /**
     * Mark the test as skipped.
     *
     * @param string $message
     *
     * @throws SkippedTestError
     *
     * @since Method available since Release 3.0.0
     */
    public static function markTestSkipped($message = '')
    {
        throw new SkippedTestError($message);
    }

    /**
     * Return the current assertion count.
     *
     * @return int
     *
     * @since Method available since Release 3.3.3
     */
    public static function getCount()
    {
        return self::$count;
    }

    /**
     * Reset the assertion counter.
     *
     * @since Method available since Release 3.3.3
     */
    public static function resetCount()
    {
        self::$count = 0;
    }
}
