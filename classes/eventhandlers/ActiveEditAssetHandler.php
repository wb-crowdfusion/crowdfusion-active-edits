<?php
 /**
 * No Summary
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted under the terms of the BSD License.
 *
 * @package     CrowdFusion
 * @copyright   2009 Crowd Fusion Inc.
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version     $Id: MediaHandler.php 1141 2009-09-30 02:21:43Z ruswerner $
 */

 /**
 * No Summary
 *
 * @package     CrowdFusion
 */



class ActiveEditAssetHandler {


    /////////////////////
    // HANDLER ACTIONS //
    /////////////////////

    /* Bound to "cms-head" to include the JavaScript files and CSS needed to enable the client-side functionality */
    public function generateAsset(Transport $output)
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
