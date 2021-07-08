<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Hooks;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class PageRendererHook
{
    protected function isFrontend(): bool
    {
        return isset($GLOBALS['TYPO3_REQUEST']) && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend();
    }

    protected function getPageData(): ?array
    {
        if ($uid = $GLOBALS['TSFE'] && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController ? (int)$GLOBALS['TSFE']->id : null) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll();

            return $queryBuilder->select('critical_css_disabled', 'critical_css_actual', 'critical_css')
                ->from('pages')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)))
                ->setMaxResults(1)
                ->execute()
                ->fetch();
        }

        return null;
    }

    protected function getCriticalCss(): ?string
    {
        if (!empty($pageData = $this->getPageData()) && empty($pageData['critical_css_disabled']) && !empty($pageData['critical_css'])) {
            return $pageData['critical_css'];
        }

        return null;
    }

    public function addCriticalCss(array &$params): void
    {
        if ($this->isFrontend() && $criticalCss = $this->getCriticalCss()) {

            // Move all styles to the footer
            $params['footerData'][] = $params['cssFiles'];

            // Remove styles
            $params['cssFiles'] = '';

            // Add critical css inline into the head
            $params['cssInline'] .= '<style>/*<![CDATA[*/ <!--/*z7_critical_css*/ ' . $criticalCss . ' -->/*]]>*/</style>';
        }
    }
}
