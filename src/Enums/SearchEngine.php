<?php

declare(strict_types=1);

namespace PhpHive\Cli\Enums;

/**
 * Search Engine Enumeration.
 *
 * Defines all supported search engines for full-text search.
 *
 * Usage:
 * ```php
 * $engine = SearchEngine::MEILISEARCH->value; // 'meilisearch'
 * $name = SearchEngine::MEILISEARCH->getName(); // 'Meilisearch'
 * ```
 */
enum SearchEngine: string
{
    /**
     * No Search Engine.
     */
    case NONE = 'none';

    /**
     * Meilisearch.
     *
     * Fast, typo-tolerant search engine.
     * Best for: Most applications, e-commerce, documentation.
     *
     * Features:
     * - Very fast
     * - Typo tolerance
     * - Easy to use
     * - Instant search
     * - Faceted search
     */
    case MEILISEARCH = 'meilisearch';

    /**
     * Elasticsearch.
     *
     * Full-featured search and analytics engine.
     * Best for: Complex search needs, analytics, logging.
     *
     * Features:
     * - Powerful query DSL
     * - Aggregations
     * - Full-text search
     * - Distributed
     * - Scalable
     */
    case ELASTICSEARCH = 'elasticsearch';

    /**
     * OpenSearch.
     *
     * AWS managed Elasticsearch fork.
     * Best for: AWS-hosted applications.
     *
     * Features:
     * - Fully managed
     * - Elasticsearch compatible
     * - AWS integration
     * - Scalable
     */
    case OPENSEARCH = 'opensearch';

    /**
     * Algolia.
     *
     * Hosted search API.
     * Best for: SaaS applications, when you want fully managed.
     *
     * Features:
     * - Fully hosted
     * - Very fast
     * - Easy integration
     * - Analytics
     * - Pay per use
     */
    case ALGOLIA = 'algolia';

    /**
     * Get choices for prompts.
     */
    public static function choices(): array
    {
        return [
            'None' => self::NONE->value,
            'Meilisearch (Fast, typo-tolerant)' => self::MEILISEARCH->value,
            'Elasticsearch (Full-featured, self-hosted)' => self::ELASTICSEARCH->value,
            'AWS OpenSearch (Managed cloud service)' => self::OPENSEARCH->value,
        ];
    }

    /**
     * Get the display name.
     */
    public function getName(): string
    {
        return match ($this) {
            self::NONE => 'None',
            self::MEILISEARCH => 'Meilisearch',
            self::ELASTICSEARCH => 'Elasticsearch',
            self::OPENSEARCH => 'OpenSearch',
            self::ALGOLIA => 'Algolia',
        };
    }
}
