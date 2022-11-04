<?php

namespace Gibbon\Data;

class PasswordPolicy
{

    /**
     * Password policy is not set.
     *
     * If this is set to true, any validate or evaluate will throw exception.
     *
     * @var bool
     */
    protected $notSet = false;

    /**
     * Password should include both upper and lower cases alphabets.
     *
     * @var bool
     */
    protected $alpha = false;

    /**
     * Password should include numerical numbers.
     *
     * @var bool
     */
    protected $numeric = false;

    /**
     * Password should include punctuation character (i.e. not alpha-numeric character).
     *
     * @var bool
     */
    protected $punctuation = false;

    /**
     * The minimal length of the password.
     *
     * @var int
     */
    protected $minLength = 0;

    /**
     * Class constructor.
     *
     * @param boolean $alpha        Password should include both upper and lower cases alphabets.
     * @param boolean $numeric      Password should include numerical numbers.
     * @param boolean $punctuation  Password should include punctuation character (i.e. not alpha-numeric character).
     * @param integer $minLength    The minimal length of the password.
     */
    public function __construct(
        bool $alpha,
        bool $numeric,
        bool $punctuation,
        int $minLength
    )
    {
        $this->alpha = $alpha;
        $this->numeric = $numeric;
        $this->punctuation = $punctuation;
        $this->minLength = $minLength;
    }

    /**
     * Create a nil policy, which always fails any password validation.
     *
     * @return static
     */
    public static function createNilPolicy()
    {
        $instance = new static(false, false, false, false);
        $instance->notSet = true;
        return $instance;
    }

    /**
     * Check if a password is valid.
     *
     * @param string $password
     *
     * @return bool  True if the password is valid according to policy. False if not.
     *
     * @throws \Exception if password policy has not been setup properly.
     */
    public function validate(string $password): bool
    {
        return empty($this->evaluate($password));
    }

    /**
     * Evaluate a password to see if it matches the password policy / policies.
     * Returns an array of problems found, or an empty array. The problems
     * are already translated with __().
     *
     * @param string $password
     *
     * @return string[]
     *
     * @throws \Exception if password policy has not been setup properly.
     */
    public function evaluate(string $password): array
    {
        $errors = [];
        if ($this->notSet) {
            throw new \Exception(__('Internal Error: Password policy setting incorrect.'));
        }
        if ($this->alpha) {
            if (preg_match('/[A-Z]+/', $password) !== 1) {
                $errors[] = __('Require at least one uppercase letter.');
            }
            if (preg_match('/[a-z]+/', $password) !== 1) {
                $errors[] = __('Require at least one lowercase letter.');
            }
        }
        if ($this->numeric) {
            if (preg_match('/[0-9]+/', $password) !== 1) {
                $errors[] = __('Require at least one number.');
            }
        }
        if ($this->punctuation) {
            if (preg_match('/[^a-zA-Z0-9]/', $password) !== 1) {
                $errors[] = __('Require at least one non-alphanumeric character (e.g. a punctuation mark or space).');
            }
        }
        if ($this->minLength > 0) {
            if (strlen($password) < $this->minLength) {
                $errors[] = sprintf(__('Must be at least %1$s characters in length.'), $this->minLength);
            }
        }
        return $errors;
    }

    /**
     * Generates a random password based on the policy rules.
     *
     * @return string  Password generated
     *
     * @throws \Exception  If for some reason, unable to generate a valid password in
     *                     reasonable time.
     */
    public function generate(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        if ($this->alpha) {
            $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        if ($this->numeric) {
            $chars .= '0123456789';
        }
        if ($this->punctuation) {
            $chars .= '!@#$%^&*?';
        }

        // Make sure: 8 <= password length <= 255
        $length = ($this->minLength < 8) ? 8 : (($this->minLength > 255) ? 255 : $this->minLength);

        // Randomize password until the password matches its own rules
        // or ran out of grace in trying.
        $grace = 100; // Just to be safe, usually works with the first trial
        $c = $grace;
        $max = strlen($chars) - 1;
        do {
            $password = '';
            for ($i=0; $i<$length; ++$i) {
                $password .= substr($chars, rand(0, $max), 1);
            }
        } while (!empty($this->evaluate($password)) && --$c > 0);

        // Final check.
        if (!empty($this->evaluate($password))) {
            throw new \Exception(sprintf('Failed to generate password within %d trial', $grace));
        }

        return $password;
    }

    /**
     * Returns an array of human-readable strings explaining the rules this
     * policy is enforcing. All rule descriptions are translated by __().
     *
     * @return string[]
     */
    public function describe(): array
    {
        $rules = [];
        if ($this->notSet) {
            throw new \Exception(__('Internal Error: Password policy setting incorrect.'));
        }
        if ($this->alpha) {
            $rules[] = __('Contain at least one lowercase letter, and one uppercase letter.');
        }
        if ($this->numeric) {
            $rules[] = __('Contain at least one number.');
        }
        if ($this->punctuation) {
            $rules[] = __('Contain at least one non-alphanumeric character (e.g. a punctuation mark or space).');
        }
        if ($this->minLength > 0) {
            $rules[] = sprintf(__('Must be at least %1$s characters in length.'), $this->minLength);
        }
        return $rules;
    }

    /**
     * Returns an unorder list describing the policies.
     * If there is no policy at all, returns empty.
     *
     * @return string
     */
    public function describeHTML(): string
    {
        try {
            $descriptions = $this->describe();
        } catch (\Exception $e) {
            return __('An error occurred.');
        }

        if (empty($descriptions)) {
            return '';
        }

        $output = __('The password policy stipulates that passwords must:').'<br/>';
        $output .= '<ul>';
        $output .= implode('', array_map(function ($description) {
            return '<li>' . $description . '</li>';
        }, $descriptions));
        $output .= '</ul>';
        return $output;
    }
}
