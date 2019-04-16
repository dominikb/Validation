<?php

/*
 * This file is part of Respect/Validation.
 *
 * (c) Alexandre Gomes Gaigalas <alexandre@gaigalas.net>
 *
 * For the full copyright and license information, please view the "LICENSE.md"
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Respect\Validation\Rules;

use Respect\Validation\Exceptions\ComponentException;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validatable;
use Respect\Validation\Validator;
use function array_keys;
use function in_array;

/**
 * @author Henrique Moody <henriquemoody@gmail.com>
 */
final class KeyValue extends AbstractRule
{
    /**
     * @var int|string
     */
    private $comparedKey;

    /**
     * @var string
     */
    private $ruleName;

    /**
     * @var int|string
     */
    private $baseKey;

    /**
     * @param int|string $comparedKey
     * @param int|string $baseKey
     */
    public function __construct($comparedKey, string $ruleName, $baseKey)
    {
        $this->comparedKey = $comparedKey;
        $this->ruleName = $ruleName;
        $this->baseKey = $baseKey;
    }

    /**
     * @param mixed $input
     */
    private function getRule($input): Validatable
    {
        if (!isset($input[$this->comparedKey])) {
            throw $this->reportError($this->comparedKey);
        }

        if (!isset($input[$this->baseKey])) {
            throw $this->reportError($this->baseKey);
        }

        try {
            $rule = Validator::__callStatic($this->ruleName, [$input[$this->baseKey]]);
            $rule->setName($this->comparedKey);
        } catch (ComponentException $exception) {
            throw $this->reportError($input, ['component' => true]);
        }

        return $rule;
    }

    private function overwriteExceptionParams(ValidationException $exception): ValidationException
    {
        $params = [];
        foreach (array_keys($exception->getParams()) as $key) {
            if (in_array($key, ['template', 'translator'])) {
                continue;
            }

            $params[$key] = $this->baseKey;
        }
        $params['name'] = $this->comparedKey;

        $exception->updateParams($params);

        return $exception;
    }

    /**
     * {@inheritDoc}
     */
    public function assert($input): void
    {
        $rule = $this->getRule($input);

        try {
            $rule->assert($input[$this->comparedKey]);
        } catch (ValidationException $exception) {
            throw $this->overwriteExceptionParams($exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function check($input): void
    {
        $rule = $this->getRule($input);

        try {
            $rule->check($input[$this->comparedKey]);
        } catch (ValidationException $exception) {
            throw $this->overwriteExceptionParams($exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validate($input): bool
    {
        try {
            $rule = $this->getRule($input);
        } catch (ValidationException $e) {
            return false;
        }

        return $rule->validate($input[$this->comparedKey]);
    }
}
