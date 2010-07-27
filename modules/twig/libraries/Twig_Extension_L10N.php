<?php
/**
 * Twig extension defining the {% trans %} and {% blocktrans %} tags to assist 
 * in gettext integration for localization.
 *
 * See also: http://docs.djangoproject.com/en/dev/topics/i18n/
 *
 * @package    Twig_Module
 * @subpackage libraries
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Twig_Extension_L10N extends Twig_Extension
{
    /**
     * Return the name of this extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'L10N';
    }

    /**
     * Define the set of filters made available by this extension.
     */
    public function getFilters()
    {
        $filters = array(
            'trans' => new Twig_Filter_Trans(),
        );

        return $filters;
    }

    /**
     * Define a set of token parsers for this extension.
     */
    public function getTokenParsers()
    {
        return array(
            new Twig_L10N_Extension_Trans_TokenParser(),
            new Twig_L10N_Extension_BlockTrans_TokenParser()
        );
    }

}

/**
 * Departs from Django, but filter wraps strings in gettext() calls.
 *
 * "foo bar baz" | trans
 */
class Twig_Filter_Trans extends Twig_Filter
{
    public function compile()
    {
        return 'gettext';
    }
}

/**
 * Tag for use in marking up strings for translation
 * {% trans "...msgid..." [ctxt "...msgctx..."] %}
 */
class Twig_L10N_Extension_Trans_TokenParser extends Twig_TokenParser
{
    /**
     * Return the name of this tag.
     */
    public function getTag() { return 'trans'; }

    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        
        // Grab the string to be translated
        $msgid = $this->parser->getExpressionParser()->parseExpression();

        // Check for optional message context
        if (!$stream->test(Twig_Token::NAME_TYPE, 'ctxt')) {
            $ctxt = false;
        } else {
            $stream->expect(Twig_Token::NAME_TYPE, 'ctxt');
            $ctxt = $stream->expect(Twig_Token::STRING_TYPE)->getValue();
        } 

        // Expect the end of the tag
        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        return new Twig_L10N_Extension_Trans_Node(
            $msgid, $ctxt, $lineno, $this->getTag()
        );
    }
}

/**
 * Translation node representation.
 */
class Twig_L10N_Extension_Trans_Node extends Twig_Node
{
    protected $msgid;
    protected $ctxt;

    public function __construct(Twig_Node_Expression $msgid, $ctxt, $lineno, $tag)
    {
        parent::__construct(
            array(),
            array(
                'msgid' => $msgid,
                'ctxt' => $ctxt,
            ),
            $lineno
        );
    }

    public function compile($compiler)
    {
        $compiler->addDebugInfo($this)->write('echo ');

        if (false === $this['ctxt']) {
            $compiler
                ->raw('gettext(')->subcompile($this['msgid'])->raw(')');
        } else {
            $compiler
                ->raw('pgettext(')->string($this['ctxt'])
                ->raw(', ')->subcompile($this['msgid'])->raw(");\n");
        }
        $compiler->raw(";\n");
    }
}

/**
 * Tag for use in marking up blocks for translation
 *
 * {% blocktrans %} ... {% endblocktrans %}
 *
 */
class Twig_L10N_Extension_BlockTrans_TokenParser extends Twig_TokenParser
{
    /**
     * Return the name of this tag.
     */
    public function getTag()
    {
        return 'blocktrans';
    }

    public function parse(Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $count = false;
        $ctxt  = false;
        $fmts  = array(array());
        $vars  = array();

        // Try parsing optional parameters to {% blocktrans %}
        while (true) {
            if ($stream->test(Twig_Token::NAME_TYPE, 'count')) {
                // Check for optional plural counter
                $stream->expect(Twig_Token::NAME_TYPE, 'count');
                $count = $this->parser->getExpressionParser()->parseExpression();
            } else if ($stream->test(Twig_Token::NAME_TYPE, 'ctxt')) {
                // Check for optional message context
                $stream->expect(Twig_Token::NAME_TYPE, 'ctxt');
                $ctxt = $stream->expect(Twig_Token::STRING_TYPE)->getValue();
            // } else if ($stream->test(Twig_Token::NAME_TYPE, 'with')) {
            //     $stream->expect(Twig_Token::NAME_TYPE, 'with');
            //  TODO: Handle variable aliases here - eg. with foo|e as bar and ...
            } else {
                $stream->expect(Twig_Token::BLOCK_END_TYPE);
                break;
            }
        }

        // Parse through message body.
        while (true) {
            $t = $stream->next();

            if ($t->test(Twig_Token::TEXT_TYPE)) {
            
                // Add plain text to format string accumulator.
                $txt = ''.$t->getValue();
                $fmts[0][] = $txt;
                continue;
            
            } else if ($t->test(Twig_Token::VAR_START_TYPE)) {
                
                // Convert variables into sprintf placeholders
                $var_t = $stream->expect(Twig_Token::NAME_TYPE);
                $stream->expect(Twig_Token::VAR_END_TYPE);

                // Get the variable name and a corresponding index.
                // Try to reuse indexes for each name.
                $var_name = $var_t->getValue();
                $var_idx = array_search($var_name, $vars);
                if (FALSE === $var_idx) {
                    // If the name has not been seen before, append it to the 
                    // list to get a new index.
                    $var_idx = count($vars);
                    $vars[] = $var_name;
                }

                // Add the sprintf placeholder to the format string accumulator.
                $fmts[0][] = '%'.($var_idx+1).'$s';
                continue;

            } else if ($t->test(Twig_Token::BLOCK_START_TYPE)) {
            
                if ($stream->test(Twig_Token::NAME_TYPE, 'plural')) {
                    // Handle {% plural %}
                    $stream->expect(Twig_Token::NAME_TYPE, 'plural');
                    $stream->expect(Twig_Token::BLOCK_END_TYPE);
                    array_unshift($fmts, array());
                    continue;
                } else {
                    // Handle {% endblocktrans %}
                    $stream->expect(Twig_Token::NAME_TYPE, 'end'.$this->getTag());
                    $stream->expect(Twig_Token::BLOCK_END_TYPE);
                    break;
                }

            }
            
            // The only thing valid by this point is EOF.
            $stream->expect(Twig_Token::EOF_TYPE);
            break;
        }

        return new Twig_L10N_Extension_BlockTrans_Node(
            $fmts, $vars, $count, $ctxt, $lineno, $this->getTag()
        );
    }

}

/**
 * Block translation node representation
 */
class Twig_L10N_Extension_BlockTrans_Node extends Twig_Node
{
    /*
    protected $fmt;
    protected $vars;
    protected $ctxt;
     */

    public function __construct($fmts, $vars, $count, $ctxt, $lineno, $tag)
    {
        parent::__construct(
            array(),
            array(
                'fmts' => $fmts,
                'vars' => $vars,
                'count' => $count,
                'ctxt' => $ctxt,
                'tag' => $tag,
            ),
            $lineno
        );
    }

    /**
     * Build the PHP source line based on the localization tag node.
     *
     * @param Twig_CompilerInterface The Twig compiler instance
     */
    public function compile($compiler)
    {
        $compiler->addDebugInfo($this);

        if (!empty($this['vars'])) {
            // Write out pairings of sprintf placeholders and original var 
            // names as i18n comments for extraction, to provide a little 
            // context.
            foreach ($this['vars'] as $var_idx=>$var_name) {
                $compiler->write("// i18n: %".($var_idx+1)."\$s = {$var_name}\n");
            }
        }
        $compiler->write('echo ');

        if (!empty($this['vars'])) {
            // Start using a sprintf if we have vars
            $compiler->raw('sprintf(');
        }

        $no_context  = (FALSE === $this['ctxt']);
        $is_singular = (count($this['fmts']) == 1);

        // Pick the appropriate gettext function based on context / plurality
        $fn = $no_context ? 
            ( $is_singular ? 'gettext' : 'ngettext' ) :
            ( $is_singular ? 'pgettext' : 'npgettext' ) ;
        $compiler->raw("{$fn}(");

        if (!$no_context) {
            // If there's a context, insert the parameter
            $compiler->string($this['ctxt'])->raw(',');
        }

        if (!$is_singular) {
            // If this is plural, insert the singular string
            $compiler->string(join(' ', $this['fmts'][1]))->raw(',');
        }

        // Insert the next parameter, which maybe the plural or singular string 
        // depending on plurality.
        $compiler->string(join('', $this['fmts'][0]));

        if (!$is_singular) {
            // Insert the counter for plurality, if necessary
            $compiler->raw(',')->subcompile($this['count']);
        }

        // Finish up the gettext function call
        $compiler->raw(')');

        if (!empty($this['vars'])) {
            // Insert the sprintf variables if needed.
            foreach ($this['vars'] as $var_name) {
                $compiler->raw(sprintf(
                    ', (isset($context[\'%s\']) ? $context[\'%s\'] : null)', 
                    $var_name, $var_name
                ));
            }
            $compiler->raw(')');
        }

        // Finish up the line.
        $compiler->raw(";\n");
    }
}
