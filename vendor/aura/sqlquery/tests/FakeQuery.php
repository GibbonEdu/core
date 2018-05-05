<?php
namespace Aura\SqlQuery;

class FakeQuery extends AbstractQuery
{
    public function build()
    {
        return 'Hello Query!';
    }

    public function quoteName($text)
    {
        return parent::quoteName($text);
    }

    public function quoteNamesIn($text)
    {
        return parent::quoteNamesIn($text);
    }
}
