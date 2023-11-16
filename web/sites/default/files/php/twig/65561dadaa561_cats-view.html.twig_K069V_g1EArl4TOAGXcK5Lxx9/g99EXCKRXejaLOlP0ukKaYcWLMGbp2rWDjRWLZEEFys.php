<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* modules/custom/helper/templates/cats-view.html.twig */
class __TwigTemplate_b96173d9dd4a63f4cef1defafe1e44a1 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 10
        echo "<table id=\"cats-table\">
  <thead>
  <tr>
    <th>";
        // line 13
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Name Cat"));
        echo "</th>
    <th>";
        // line 14
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("User Email"));
        echo "</th>
    <th>";
        // line 15
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Photo Cat"));
        echo "</th>
    <th>";
        // line 16
        echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Created"));
        echo "</th>
    ";
        // line 17
        if (($context["is_admin"] ?? null)) {
            // line 18
            echo "      <th>";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Operations"));
            echo "</th>
    ";
        }
        // line 20
        echo "  </tr>
  </thead>
  <tbody>
  ";
        // line 23
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["content"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
            // line 24
            echo "    <tr>
      <td>";
            // line 25
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["row"], "cat_name", [], "any", false, false, true, 25), 25, $this->source), "html", null, true);
            echo "</td>
      <td>";
            // line 26
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["row"], "user_email", [], "any", false, false, true, 26), 26, $this->source), "html", null, true);
            echo "</td>
      <td><div class=\"image-container\" id=\"image-container-";
            // line 27
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["row"], "id_image", [], "any", false, false, true, 27), 27, $this->source), "html", null, true);
            echo "\"><img class=\"responsive-image\" id=\"responsive-image-";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["row"], "id_image", [], "any", false, false, true, 27), 27, $this->source), "html", null, true);
            echo "\" src=\"";
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["row"], "cats_image", [], "any", false, false, true, 27), 27, $this->source), "html", null, true);
            echo "\" alt=\"Cat Image\"></div></td>
      <td>";
            // line 28
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["row"], "created", [], "any", false, false, true, 28), 28, $this->source), "html", null, true);
            echo "</td>
      ";
            // line 29
            if (($context["is_admin"] ?? null)) {
                // line 30
                echo "      <td>";
                echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, $context["row"], "control", [], "any", false, false, true, 30), 30, $this->source), "html", null, true);
                echo "</td>
      ";
            }
            // line 32
            echo "    </tr>
  ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['row'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 34
        echo "  </tbody>
</table>
";
    }

    public function getTemplateName()
    {
        return "modules/custom/helper/templates/cats-view.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  115 => 34,  108 => 32,  102 => 30,  100 => 29,  96 => 28,  88 => 27,  84 => 26,  80 => 25,  77 => 24,  73 => 23,  68 => 20,  62 => 18,  60 => 17,  56 => 16,  52 => 15,  48 => 14,  44 => 13,  39 => 10,);
    }

    public function getSourceContext()
    {
        return new Source("", "modules/custom/helper/templates/cats-view.html.twig", "/var/www/web/modules/custom/helper/templates/cats-view.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("if" => 17, "for" => 23);
        static $filters = array("t" => 13, "escape" => 25);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['if', 'for'],
                ['t', 'escape'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
