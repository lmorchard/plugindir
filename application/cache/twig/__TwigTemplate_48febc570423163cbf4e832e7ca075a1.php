<?php

/* layout.html */
class __TwigTemplate_48febc570423163cbf4e832e7ca075a1 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->blocks = array(
            'title' => array(array($this, 'block_title')),
            'head' => array(array($this, 'block_head')),
            'content' => array(array($this, 'block_content')),
            'js_includes' => array(array($this, 'block_js_includes')),
            'js_defines' => array(array($this, 'block_js_defines')),
        );
    }

    public function display(array $context)
    {
        // line 1
        echo "<!DOCTYPE html>
<html lang=\"";
        // line 2
        echo (isset($context['l10n_language']) ? $context['l10n_language'] : null);
        echo "\" dir=\"";
        echo (isset($context['l10n_dir']) ? $context['l10n_dir'] : null);
        echo "\">
    <head>
        ";
        // line 4
        $this->getBlock('head', $context);
        // line 8
        echo "    </head>
    <body id=\"ctrl_";
        // line 10
        echo (isset($context['router_controller']) ? $context['router_controller'] : null);
        echo "_act_";
        echo (isset($context['router_method']) ? $context['router_method'] : null);
        echo "\"
        class=\"noJS ctrl_";
        // line 11
        echo (isset($context['router_controller']) ? $context['router_controller'] : null);
        echo " act_";
        echo (isset($context['router_method']) ? $context['router_method'] : null);
        echo " ctrl_";
        echo (isset($context['router_controller']) ? $context['router_controller'] : null);
        echo "_act_";
        echo (isset($context['router_method']) ? $context['router_method'] : null);
        echo " l10n-lang-";
        echo (isset($context['l10n_language']) ? $context['l10n_language'] : null);
        echo " l10n-dir-";
        echo (isset($context['l10n_dir']) ? $context['l10n_dir'] : null);
        echo "\">
        <div id=\"main\">
            <div class=\"header\">
                <h1 class=\"title\">
                    <a href=\"";
        // line 15
        echo (isset($context['base_url']) ? $context['base_url'] : null);
        echo "\" class=\"home\"><span class=\"mozilla\">";
        echo gettext("Mozilla");
        echo "</span> ";
        echo gettext("Plugin Directory");
        echo "</a> &raquo; 
                    ";
        // line 16
        $this->getBlock('title', $context);
        echo "                </h1>
                <div class=\"secondary\">
                    <ul class=\"auth clearfix\">
                        ";
        // line 20
        if ((isset($context['is_logged_in']) ? $context['is_logged_in'] : null)) {
            echo "                            <li><a href=\"";
            // line 21
            echo (isset($context['base_url']) ? $context['base_url'] : null);
            echo "profiles/";
            echo twig_escape_filter($this->env, twig_urlencode_filter($this->getAttribute((isset($context['authprofile']) ? $context['authprofile'] : null), "screen_name", array(), "any")));
            echo "/plugins\">";
            echo $this->getAttribute((isset($context['authprofile']) ? $context['authprofile'] : null), "screen_name", array(), "any");
            echo "</a></li>
                            <li><a href=\"";
            // line 22
            echo (isset($context['base_url']) ? $context['base_url'] : null);
            echo "profiles/";
            echo twig_escape_filter($this->env, twig_urlencode_filter($this->getAttribute((isset($context['authprofile']) ? $context['authprofile'] : null), "screen_name", array(), "any")));
            echo "/settings\">";
            echo gettext("settings");
            echo "</a></li>
                            <li><a href=\"";
            // line 23
            echo (isset($context['base_url']) ? $context['base_url'] : null);
            echo "logout\">";
            echo gettext("Log out");
            echo "</a></li>
                        ";
        } else {
            // line 24
            echo "                            <li><a href=\"";
            // line 25
            echo (isset($context['base_url']) ? $context['base_url'] : null);
            echo "login\">";
            echo gettext("Log in");
            echo "</a></li>
                            <li><a href=\"";
            // line 26
            echo (isset($context['base_url']) ? $context['base_url'] : null);
            echo "register\">";
            echo gettext("Register");
            echo "</a></li>
                        ";
        }
        // line 27
        echo "                        <li class=\"search\">
                            <form method=\"get\" action=\"";
        // line 29
        echo (isset($context['base_url']) ? $context['base_url'] : null);
        echo "search/results\">
                                <div id=\"simple_search\">
                                    <input id=\"q\" name=\"q\" value=\"";
        // line 31
        echo twig_escape_filter($this->env, (isset($context['q']) ? $context['q'] : null));
        echo "\" 
                                        placeholder=\"";
        // line 32
        echo gettext("search releases");
        echo "\" />
                                    ";
        // line 37
        echo "
                                </div>
                                <fieldset id=\"advanced_search\">
                                    <legend>";
        // line 40
        echo gettext("Advanced search");
        echo "</legend>
                                </fieldset>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
            <div class=\"content\">
                ";
        // line 48
        if ((isset($context['flash_message']) ? $context['flash_message'] : null)) {
            echo "                    <p class=\"flash_message\">";
            // line 49
            echo twig_escape_filter($this->env, (isset($context['flash_message']) ? $context['flash_message'] : null));
            echo "</p>
                ";
        }
        // line 50
        echo "                ";
        // line 51
        $this->getBlock('content', $context);
        echo "            </div>
        </div>
        ";
        // line 54
        $this->getBlock('js_includes', $context);
        // line 69
        echo "        <script type=\"text/javascript\">
            ";
        // line 71
        $this->getBlock('js_defines', $context);
        // line 83
        echo "        </script>
    </body>
</html>
";
    }

    // line 5
    public function block_title($context, $parents)
    {
    }

    // line 4
    public function block_head($context, $parents)
    {
        echo "            <title>";
        // line 5
        $this->getBlock('title', $context);
        echo " :: ";
        echo gettext("Mozilla Plugin Directory");
        echo "</title>
            <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /> 
            <link href=\"";
        // line 7
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "css/main.css\" rel=\"stylesheet\" type=\"text/css\" />
        ";
    }

    // line 51
    public function block_content($context, $parents)
    {
    }

    // line 54
    public function block_js_includes($context, $parents)
    {
        echo "            <script type=\"text/javascript\" src=\"";
        // line 55
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "js/sprintf.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 56
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "js/json2.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 57
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "js/jquery-1.4.2.min.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 58
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "js/jquery.scrollTo-min.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 59
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "js/jquery.cloneTemplate.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 60
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "perfidies/lib/jquery.jsonp-1.1.0.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 61
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "perfidies/lib/browserdetect.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 62
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "perfidies/lib/plugindetect.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 63
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "perfidies/perfidies.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 64
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "perfidies/web.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 65
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "perfidies/exploder.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 66
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "js/PluginDir/PluginDir.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 67
        echo (isset($context['media_url']) ? $context['media_url'] : null);
        echo "js/PluginDir/Utils.js\"></script>
            <script type=\"text/javascript\" src=\"";
        // line 68
        echo (isset($context['base_url']) ? $context['base_url'] : null);
        echo "l10n/translations?callback=PluginDir.Utils.loadTranslations\"></script>
        ";
    }

    // line 71
    public function block_js_defines($context, $parents)
    {
        echo "                PluginDir.base_url = \"";
        // line 72
        echo (isset($context['base_url']) ? $context['base_url'] : null);
        echo "\";
                PluginDir.pfs_endpoint = PluginDir.base_url + \"pfs/v2\";
                ";
        // line 74
        if ($this->getAttribute((isset($context['authprofile']) ? $context['authprofile'] : null), "screen_name", array(), "any")) {
            echo "                    PluginDir.is_logged_in = true;
                    PluginDir.screen_name = \"";
            // line 76
            echo twig_escape_filter($this->env, $this->getAttribute((isset($context['authprofile']) ? $context['authprofile'] : null), "screen_name", array(), "any"));
            echo "\";
                    PluginDir.sandbox_url = PluginDir.base_url + 
                        \"profiles/";
            // line 78
            echo twig_urlencode_filter($this->getAttribute((isset($context['authprofile']) ? $context['authprofile'] : null), "screen_name", array(), "any"));
            echo "/plugins.json\";
                ";
        } else {
            // line 79
            echo "                    PluginDir.is_logged_in = false;
                    PluginDir.screen_name = false;
                ";
        }
        // line 82
        echo "            ";
    }

}
