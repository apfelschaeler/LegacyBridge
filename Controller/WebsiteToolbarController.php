<?php
/**
 * File containing the WebsiteToolbarController class.
 *
 * @copyright Copyright (C) 2014 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */
namespace eZ\Bundle\EzPublishLegacyBundle\Controller;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\Core\MVC\Symfony\Controller\Controller;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Templating\EngineInterface;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;

class WebsiteToolbarController extends Controller
{
    /** @var \Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface */
    private $csrfProvider;

    /** @var \Symfony\Component\Templating\EngineInterface */
    private $legacyTemplateEngine;

    /** @var \Symfony\Component\Security\Core\SecurityContextInterface */
    private $securityContext;

    /** @var \eZ\Publish\API\Repository\LocationService */
    private $locationService;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    public function __construct(
        CsrfProviderInterface $csrfProvider,
        EngineInterface $engine,
        ContentService $contentService,
        LocationService $locationService,
        SecurityContextInterface $securityContext
    )
    {
        $this->csrfProvider = $csrfProvider;
        $this->legacyTemplateEngine = $engine;
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->securityContext = $securityContext;
    }

    /**
     * Renders the legacy website toolbar template.
     *
     * If the logged in user doesn't have the required permission, an empty response is returned
     *
     * @param mixed $locationId
     */
    public function websiteToolbarAction( $locationId )
    {
        $response = new Response();

        $authorizationAttribute = new AuthorizationAttribute(
            'websitetoolbar',
            'use',
            array( 'valueObject' => $this->loadContentByLocationId( $locationId ) )
        );

        if ( !$this->securityContext->isGranted( $authorizationAttribute ) )
        {
            return $response;
        }

        $parameters = array( 'current_node_id' => $locationId );
        if ( isset( $this->csrfProvider ) )
        {
            $parameters['form_token'] = $this->csrfProvider->generateCsrfToken( 'legacy' );
        }

        $response->setContent(
            $this->legacyTemplateEngine->render( 'design:parts/website_toolbar.tpl', $parameters )
        );

        return $response;
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    protected function loadContentByLocationId( $locationId )
    {
        return $this->contentService->loadContent(
            $this->locationService->loadLocation( $locationId )->contentId
        );
    }
}

