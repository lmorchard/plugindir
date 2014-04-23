<?php

/* index/index_byname.html */
class __TwigTemplate_db26df29f3d30a8e14633286d8885ec6 extends Twig_Template
{
    protected $parent;

    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->blocks = array(
            'index_content' => array(array($this, 'block_index_content')),
        );
    }

    public function display(array $context)
    {
        if (null === $this->parent) {
            $this->parent = clone $this->env->loadTemplate("index/index_shell.html");
            $this->parent->pushBlocks($this->blocks);
        }
        $this->parent->display($context);
    }

    // line 3
    public function block_index_content($context, $parents)
    {
        echo "    <p>";
        // line 4
        echo gettext("        The following is a list of plugins by name, along with a count of
        releases for each plugin.
    ");
        // line 7
        echo "</p>
    <ul class=\"counts name_counts\">
        ";
        // line 9
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_iterator_to_array((isset($context['name_counts']) ? $context['name_counts'] : null));
        $countable = is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof Countable);
        $length = $countable ? count($context['_seq']) : null;
        $context['loop'] = array(
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        );
        if ($countable) {
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context['_key'] => $context['plugin']) {
            echo "            <li>
                <span class=\"count\">(";
            // line 11
            echo twig_escape_filter($this->env, $this->getAttribute((isset($context['plugin']) ? $context['plugin'] : null), "count", array(), "any"));
            echo ")</span>
                <a href=\"";
            // line 12
            echo (isset($context['base_url']) ? $context['base_url'] : null);
            echo "plugins/detail/";
            echo twig_escape_filter($this->env, twig_urlencode_filter($this->getAttribute((isset($context['plugin']) ? $context['plugin'] : null), "pfs_id", array(), "any")));
            echo "\">";
            echo twig_escape_filter($this->env, $this->getAttribute((isset($context['plugin']) ? $context['plugin'] : null), "name", array(), "any"));
            echo "</a>
            </li>
        ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if ($countable) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['plugin'], $context['_parent'], $context['loop']);
        $context = array_merge($_parent, array_intersect_key($context, $_parent));
        // line 14
        echo "    </ul>
";
    }

}
