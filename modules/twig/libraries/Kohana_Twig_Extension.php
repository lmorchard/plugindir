<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Kohana extensions to Twig, makes certain helpers and suchlike available from 
 * Twig templates.
 *
 * @package    Twig_Module
 * @subpackage libraries
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 */
class Kohana_Twig_Extension extends Twig_Extension
{

    /**
     * Return the name of this extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'kohana';
    }

    /**
     * Define a set of token parsers for this extension.
     */
    public function getTokenParsers()
    {
        return array(
            new Kohana_Twig_Trans_TokenParser(),
            new Kohana_Twig_BlockTrans_TokenParser()
        );
    }

    /**
     * Define the set of filters made available by this extension.
     */
    public function getFilters()
    {
        $filters = array(
            'phpeval'  => array('Kohana_Twig_Extension::filter_phpeval', false),
            'explode'  => array('Kohana_Twig_Extension::filter_explode', false),
            'json'     => array('Kohana_Twig_Extension::filter_json', false),
            'fromjson' => array('Kohana_Twig_Extension::filter_fromjson', false),
        );

        return $filters;
    }

    /**
     * This is a nasty escape hatch for PHP one-liners in Twig templates.  When 
     * used sparingly in macros, it's helpful for accessing Kohana helpers.
     */
    public static function filter_phpeval($string)
    {
        return eval($string);
    }

    /**
     *
     */
    public static function filter_explode($string, $delim=' ')
    {
        return explode($delim, $string);
    }

    /**
     * Convert a data structure into a JSON string.
     */
    public static function filter_json($data)
    {
        return json_encode($data);
    }

    /**
     * Decode a data structure expressed as JSON
     */
    public static function filter_fromjson($string)
    {
        return json_decode($string, true);
    }

}

/**
 * Tag for use in marking up strings for translation
 * {% trans "...msgid..." [ctxt "...msgctx..."] %}
 */
class Kohana_Twig_Trans_TokenParser extends Twig_TokenParser
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

        return new Kohana_Twig_Trans_Node(
            $msgid, $ctxt, $lineno, $this->getTag()
        );
    }
}

/**
 * Translation node representation.
 */
class Kohana_Twig_Trans_Node extends Twig_Node
{
    protected $msgid;
    protected $ctxt;

    public function __construct(Twig_Node_Expression $msgid, $ctxt, $lineno, $tag)
    {
        parent::__construct($lineno);
        $this->msgid = $msgid;
        $this->ctxt = $ctxt;
    }

    public function compile($compiler)
    {
        $compiler->addDebugInfo($this)->write('echo ');

        if (false === $this->ctxt) {
            $compiler
                ->write('_(')->subcompile($this->msgid)->write(')');
        } else {
            $compiler
                ->write('pgettext(')->string($this->ctxt)
                ->write(', ')->subcompile($this->msgid)->raw(");\n");
        }
        $compiler->raw(";\n");
    }
}

/**
 * Tag for use in marking up strings for translation
 * {% trans "..." %}
 */
class Kohana_Twig_BlockTrans_TokenParser extends Twig_TokenParser
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
                $txt = trim(preg_replace('/\s+/', ' ', str_replace("\\n", ' ', $txt)));
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

        return new Kohana_Twig_BlockTrans_Node(
            $fmts, $vars, $count, $ctxt, $lineno, $this->getTag()
        );
    }

}

/**
 * Block translation node representation
 */
class Kohana_Twig_BlockTrans_Node extends Twig_Node
{
    protected $fmt;
    protected $vars;
    protected $ctxt;

    public function __construct($fmts, $vars, $count, $ctxt, $lineno, $tag)
    {
        parent::__construct($lineno);
        $this->fmts  = $fmts;
        $this->vars  = $vars;
        $this->count = $count;
        $this->ctxt  = $ctxt;
        $this->tag   = $tag;
    }

    public function compile($compiler)
    {
        $compiler->addDebugInfo($this)->write('echo ');

        if (!empty($this->vars)) {
            $compiler->raw('sprintf(');
        }

        $no_context  = (FALSE === $this->ctxt);
        $is_singular = (count($this->fmts) == 1);

        $fn = $no_context ? 
            ( $is_singular ? 'gettext' : 'ngettext' ) :
            ( $is_singular ? 'pgettext' : 'npgettext' ) ;

        $compiler->raw("{$fn}(");

        if (!$no_context) {
            $compiler->string($this->ctxt)->raw(',');
        }

        if (!$is_singular) {
            $compiler->string(join(' ', $this->fmts[1]))->raw(',');
        }

        $compiler->string(join(' ', $this->fmts[0]));

        if (!$is_singular) {
            $compiler->raw(',')->subcompile($this->count);
        }

        $compiler->raw(')');

        if (!empty($this->vars)) {
            foreach ($this->vars as $var_name) {
                $compiler->raw(sprintf(', (isset($context[\'%s\']) ? $context[\'%s\'] : null)', $var_name, $var_name));
            }
            $compiler->raw(')');
        }

        $compiler->raw(";\n");
    }
}
