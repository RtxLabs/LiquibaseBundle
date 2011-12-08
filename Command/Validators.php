<?php

namespace RtxLabs\LiquibaseBundle\Command;

class Validators
{
    static public function validateChangelogName($changelog)
    {
        if (false === $pos = strpos($changelog, ':')) {
            throw new \InvalidArgumentException(sprintf('The changelog name must contain a : ("%s" given, expecting something like AcmeBlogBundle:CreateUserTable)', $changelog));
        }

        return $changelog;
    }
}
