<?php

/* index/index_shell.html */
class __TwigTemplate_c72c188c104e5f532675bfe20998538b extends Twig_Template
{
    protected $parent;

    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->blocks = array(
            'title' => array(array($this, 'block_title')),
            'js_includes' => array(array($this, 'block_js_includes')),
            'index_content' => array(array($this, 'block_index_content')),
            'content' => array(array($this, 'block_content')),
        );
    }

    public function display(array $context)
    {
        if (null === $this->parent) {
            $this->parent = clone $this->env->loadTemplate("layout.html");
            $this->parent->pushBlocks($this->blocks);
        }
        $this->parent->display($context);
    }

    // line 3
    public function block_title($context, $parents)
    {
        echo "    ";
        // line 4
        echo gettext("Home");
    }

    // line 7
    public function block_js_includes($context, $parents)
    {
        echo "    ";
        // line 8
        $this->getParent($context, $parents);
        echo "    <!--<script type=\"text/javascript\" src=\"";
        // line 9
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "js/PluginDir/Index.js\"></script>-->
";
    }

    // line 71
    public function block_index_content($context, $parents)
    {
    }

    // line 12
    public function block_content($context, $parents)
    {
        echo "
";
        // line 14
        $context['this'] = $this->env->loadTemplate("index/index_shell.html", true);
        // line 15
        $context['auditlogevents'] = $this->env->loadTemplate("macros/auditlogevents.html", true);
        echo "
<div class=\"index_home\">

    <div class=\"intro\">
        <h2>";
        // line 20
        echo gettext("Welcome to the Mozilla Plugin Directory");
        echo "</h2>
        <p>";
        // line 21
        echo gettext("            This site is an attempt to collect and provide information about
            third-party browser plugins installed by people across the web.  
        ");
        // line 24
        echo "</p>
        <p>";
        // line 25
        echo gettext("            If you want to get involved, you can sign up for a
            profile.  This will give you access to a sandbox in which to 
            edit plugin records, test them against our detection code in 
            multiple browsers, and submit changes to our editors for
            approval and inclusion in the database.
        ");
        // line 31
        echo "</p>
    </div>

    <div class=\"recent_events\">
        <h3>Recent activity</h3>
        <ul class=\"events\">
            ";
        // line 37
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_iterator_to_array((isset($context['events']) ? $context['events'] : null));
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
        foreach ($context['_seq'] as $context['_key'] => $context['event']) {
            echo "                <li>
                    <div class=\"event\">
                        ";
            // line 40
            echo $this->getAttribute((isset($context['auditlogevents']) ? $context['auditlogevents'] : null), "event", array((isset($context['event']) ? $context['event'] : null), (isset($context['base_url']) ? $context['base_url'] : null), ), "method");
            echo "
                    </div>
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
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['event'], $context['_parent'], $context['loop']);
        $context = array_merge($_parent, array_intersect_key($context, $_parent));
        // line 43
        echo "        </ul>
        <a class=\"view_all\" href=\"";
        // line 45
        echo (isset($context['base_url']) ? $context['base_url'] : null);
        echo "plugins/activitylog\">";
        echo gettext("View all activity");
        echo "</a>
    </div>

    <div class=\"listing\">

        <ul class=\"nav clearfix\">

            ";
        // line 60
        echo "
            ";
        // line 62
        if ((isset($context['is_logged_in']) ? $context['is_logged_in'] : null)) {
            echo "                ";
            // line 63
            echo $this->getAttribute((isset($context['this']) ? $context['this'] : null), "nav_item", array((isset($context['base_url']) ? $context['base_url'] : null), "sandbox", (("profiles/") . (twig_escape_filter($this->env, twig_urlencode_filter($this->getAttribute((isset($context['authprofile']) ? $context['authprofile'] : null), "screen_name", array(), "any"))))) . ("/plugins"), gettext("Your Sandbox"), (isset($context['by_cat']) ? $context['by_cat'] : null), $this->getAttribute((isset($context['authprofile']) ? $context['authprofile'] : null), "screen_name", array(), "any"), ), "method");
            echo "
            ";
        }
        // line 64
        echo "            ";
        // line 65
        echo $this->getAttribute((isset($context['this']) ? $context['this'] : null), "nav_item", array((isset($context['base_url']) ? $context['base_url'] : null), "name", "?by=name", gettext("By Name"), (isset($context['by_cat']) ? $context['by_cat'] : null), ), "method");
        echo "
            ";
        // line 66
        echo $this->getAttribute((isset($context['this']) ? $context['this'] : null), "nav_item", array((isset($context['base_url']) ? $context['base_url'] : null), "application", "?by=application", gettext("By Application"), (isset($context['by_cat']) ? $context['by_cat'] : null), ), "method");
        echo "
            ";
        // line 67
        echo $this->getAttribute((isset($context['this']) ? $context['this'] : null), "nav_item", array((isset($context['base_url']) ? $context['base_url'] : null), "os", "?by=os", gettext("By Operating System"), (isset($context['by_cat']) ? $context['by_cat'] : null), ), "method");
        echo "
            ";
        // line 68
        echo $this->getAttribute((isset($context['this']) ? $context['this'] : null), "nav_item", array((isset($context['base_url']) ? $context['base_url'] : null), "mimetype", "?by=mimetype", gettext("By MIME Type"), (isset($context['by_cat']) ? $context['by_cat'] : null), ), "method");
        echo "
        </ul>

        <div class=\"listing_content\">";
        // line 71
        $this->getBlock('index_content', $context);
        echo "</div>

    </div>

</div>
";
    }

    // line 52
    public function getnav_item($base_url = null, $name = null, $path = null, $label = null, $selected = null)
    {
        $context = array(
            "base_url" => $base_url,
            "name" => $name,
            "path" => $path,
            "label" => $label,
            "selected" => $selected,
        );

        echo "                <li class=\"";
        // line 53
        echo ((isset($context['name']) ? $context['name'] : null) == (isset($context['selected']) ? $context['selected'] : null)) ? ("selected") : ("");
        echo "\">
                    ";
        // line 54
        if ((isset($context['name']) ? $context['name'] : null) == (isset($context['selected']) ? $context['selected'] : null)) {
            echo "                        <span>";
            // line 55
            echo (isset($context['label']) ? $context['label'] : null);
            echo "</span>
                    ";
        } else {
            // line 56
            echo "                        <a href=\"";
            // line 57
            echo (isset($context['base_url']) ? $context['base_url'] : null);
            echo (isset($context['path']) ? $context['path'] : null);
            echo "\">";
            echo (isset($context['label']) ? $context['label'] : null);
            echo "</a>
                    ";
        }
        // line 58
        echo "                </li>
            ";
    }

}
