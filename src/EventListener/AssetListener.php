<?php

namespace CrowdFusion\Plugin\ActiveEditsPlugin\EventListener;

class AssetListener
{
    /**
     * Bound to "cms-head" to include the JavaScript files and CSS needed to enable
     * the client-side functionality.
     *
     * @param \Transport $output
     */
    public function generateAsset(\Transport $output)
    {
        $output->String .= <<<EOD
            {% asset js?src=/js/ActiveEdits.js&pack=true %}

            {% asset css?src=/css/active-edits.css&min=true %}
            {% asset css?src=/css/active-edits-ie7.css&min=true&iecond=IE 7 %}

            <script language="JavaScript" type="text/javascript">

    	        $(document).ready(function() {
                    ActiveEdits.initList({
                        {% filter activeeditconfig %}
                    });
    	        });

            </script>
EOD;
    }
}
