<?php
/**
 * Copyright 2006 - 2016 TubePress LLC (http://tubepress.com/)
 *
 * This file is part of TubePress Pro.
 *
 * License summary
 *   - Can be used on 1 site, 1 server
 *   - Cannot be resold or distributed
 *   - Commercial use allowed
 *   - Can modify source-code but cannot distribute modifications (derivative works)
 *
 * Please see http://tubepress.com/license for details.
 */

class tubepress_embedded_responsive_impl_listeners_EmbeddedJsInjector
{
    /**
     * @var tubepress_api_environment_EnvironmentInterface
     */
    private $_environment;

    /**
     * @var tubepress_api_options_ContextInterface
     */
    private $_context;

    public function __construct(tubepress_api_environment_EnvironmentInterface $environment,
                                tubepress_api_options_ContextInterface         $context)
    {
        $this->_environment = $environment;
        $this->_context     = $context;
    }

    public function onEmbeddedTemplatePostRender(tubepress_api_event_EventInterface $event)
    {
        if (!$this->_context->get(tubepress_api_options_Names::RESPONSIVE_EMBEDS)) {

            return;
        }

        $html     = $event->getSubject();
        $baseUrl  = $this->_environment->getBaseUrl()->getClone()->addPath('web/js/responsive-embeds.js');
        $jsPath   = $baseUrl->toString();
        $domId    = $this->_getIframeId($html);

        if (!$domId) {

            return;
        }

        $toAdd = <<<EOT
<script type="text/javascript">
   var tubePressDomInjector = tubePressDomInjector || [],
       tubePressResponsiveEmbeds = tubePressResponsiveEmbeds || [];
       tubePressDomInjector.push(['loadJs', '$jsPath', true ]);
       tubePressResponsiveEmbeds.push(['register', '$domId', {}]);
</script>
EOT;

        $event->setSubject($html . $toAdd);
    }

    private function _getIframeId($html)
    {
        $matchCount = preg_match_all('~\s+id="([^"]+)"\s+~i', $html, $matches);

        if ($matchCount !== 1 || !is_array($matches) || count($matches) !== 2) {

            return null;
        }

        return $matches[1][0];
    }
}