<?php

/* macros/auditlogevents.html */
class __TwigTemplate_c534493b7b90903e0d600ce5a570bc7f extends Twig_Template
{
    public function display(array $context)
    {
    }

    // line 1
    public function getevent($event = null, $base_url = null)
    {
        $context = array(
            "event" => $event,
            "base_url" => $base_url,
        );

        echo "
    <div class=\"what\">
        <a class=\"actor\" href=\"";
        // line 4
        echo (isset($context['base_url']) ? $context['base_url'] : null);
        echo "profiles/";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "profile", array(), "any"), "screen_name", array(), "any"));
        echo "\">";
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "profile", array(), "any"), "screen_name", array(), "any"));
        echo "</a>

        <span class=\"action\">
            ";
        // line 7
        if ("copied_to_sandbox" == $this->getAttribute((isset($context['event']) ? $context['event'] : null), "action", array(), "any")) {
            echo "                copied
            ";
        } elseif ("deployed_from_sandbox" == $this->getAttribute((isset($context['event']) ? $context['event'] : null), "action", array(), "any")) {
            // line 9
            echo "                deployed
            ";
        } elseif ("requested_push" == $this->getAttribute((isset($context['event']) ? $context['event'] : null), "action", array(), "any")) {
            // line 11
            echo "                requested deployment for 
            ";
        } elseif ("add_trusted" == $this->getAttribute((isset($context['event']) ? $context['event'] : null), "action", array(), "any")) {
            // line 13
            echo "                enabled trusted status on
            ";
        } elseif ("remove_trusted" == $this->getAttribute((isset($context['event']) ? $context['event'] : null), "action", array(), "any")) {
            // line 15
            echo "                removed trusted status on
            ";
        } else {
            // line 17
            echo "                ";
            // line 18
            echo $this->getAttribute((isset($context['event']) ? $context['event'] : null), "action", array(), "any");
            echo "
            ";
        }
        // line 19
        echo "        </span>

        ";
        // line 22
        if (((!$this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "plugin", array(), "any"), "name", array(), "any"))) && ($this->getAttribute((isset($context['event']) ? $context['event'] : null), "old_state", array(), "any"))) {
            echo "            <strike>";
            // line 23
            echo $this->getAttribute($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "old_state", array(), "any"), "meta", array(), "any"), "name", array(), "any");
            echo "</strike>
            ";
            // line 24
            if ($this->getAttribute($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "old_state", array(), "any"), "meta", array(), "any"), "sandbox_profile_id", array(), "any")) {
                echo "                in sandbox
            ";
            }
            // line 26
            echo "        ";
        } elseif (((!$this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "plugin", array(), "any"), "name", array(), "any"))) && ($this->getAttribute((isset($context['event']) ? $context['event'] : null), "new_state", array(), "any"))) {
            // line 27
            echo "            <strike>";
            // line 28
            echo $this->getAttribute($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "new_state", array(), "any"), "meta", array(), "any"), "name", array(), "any");
            echo "</strike>
            ";
            // line 29
            if ($this->getAttribute($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "new_state", array(), "any"), "meta", array(), "any"), "sandbox_profile_id", array(), "any")) {
                echo "                in sandbox
            ";
            }
            // line 31
            echo "        ";
        } elseif ($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "plugin", array(), "any"), "sandbox_profile_id", array(), "any")) {
            // line 32
            echo "            <a href=\"";
            // line 33
            echo (isset($context['base_url']) ? $context['base_url'] : null);
            echo "profiles/";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "plugin", array(), "any"), "sandbox_profile", array(), "any"), "screen_name", array(), "any"));
            echo "/plugins/detail/";
            echo twig_escape_filter($this->env, twig_urlencode_filter($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "plugin", array(), "any"), "pfs_id", array(), "any")));
            echo "\">";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "plugin", array(), "any"), "name", array(), "any"));
            echo "</a> in sandbox
        ";
        } else {
            // line 34
            echo "            <a href=\"";
            // line 35
            echo (isset($context['base_url']) ? $context['base_url'] : null);
            echo "plugins/detail/";
            echo twig_escape_filter($this->env, twig_urlencode_filter($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "plugin", array(), "any"), "pfs_id", array(), "any")));
            echo "\">";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "plugin", array(), "any"), "name", array(), "any"));
            echo "</a>
        ";
        }
        // line 36
        echo "
        <span class=\"details\">
            ";
        // line 39
        if ("deployed_from_sandbox" == $this->getAttribute((isset($context['event']) ? $context['event'] : null), "action", array(), "any")) {
            echo "                ";
            // line 40
            if ($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "details", array(), "any"), "sandbox_profile", array(), "any")) {
                echo "                    from <a class=\"actor\" href=\"";
                // line 41
                echo (isset($context['base_url']) ? $context['base_url'] : null);
                echo "profiles/";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "details", array(), "any"), "sandbox_profile", array(), "any"), "screen_name", array(), "any"));
                echo "\">";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "details", array(), "any"), "sandbox_profile", array(), "any"), "screen_name", array(), "any"));
                echo "</a>
                ";
            } else {
                // line 42
                echo "                    from sandbox to public
                ";
            }
            // line 44
            echo "            ";
        } elseif ("copied_to_sandbox" == $this->getAttribute((isset($context['event']) ? $context['event'] : null), "action", array(), "any")) {
            // line 45
            echo "                to sandbox
            ";
        } elseif (("remove_trusted" == $this->getAttribute((isset($context['event']) ? $context['event'] : null), "action", array(), "any")) || ("add_trusted" == $this->getAttribute((isset($context['event']) ? $context['event'] : null), "action", array(), "any"))) {
            // line 47
            echo "                for <a class=\"actor\" href=\"";
            // line 48
            echo (isset($context['base_url']) ? $context['base_url'] : null);
            echo "profiles/";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "details", array(), "any"), "profile", array(), "any"), "screen_name", array(), "any"));
            echo "\">";
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getAttribute((isset($context['event']) ? $context['event'] : null), "details", array(), "any"), "profile", array(), "any"), "screen_name", array(), "any"));
            echo "</a>
            ";
        } else {
            // line 49
            echo "                ";
            // line 50
            echo $this->getAttribute((isset($context['event']) ? $context['event'] : null), "details", array(), "any");
            echo "
            ";
        }
        // line 51
        echo "        </span>

    </div>

    <abbr class=\"when\" title=\"";
        // line 56
        echo $this->getAttribute((isset($context['event']) ? $context['event'] : null), "isotime", array(), "any");
        echo "\">";
        echo $this->getAttribute((isset($context['event']) ? $context['event'] : null), "relative_date", array(), "any");
        echo " at ";
        echo $this->getAttribute((isset($context['event']) ? $context['event'] : null), "time", array(), "any");
        echo " GMT</abbr>

";
    }

}
