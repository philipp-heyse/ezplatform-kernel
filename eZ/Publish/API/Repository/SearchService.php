<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\API\Repository;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;

/**
 * Search service.
 */
interface SearchService
{
    /**
     * Capability flag for scoring feature for use with {@see ::supports()}.
     *
     * Scoring, a search feature telling you how well one search hit scores compared to other items in the search result.
     * When this is supported you can expect search engine to populate SearchHit->score and SearchResult->maxScore
     * properties as well as sort by this if no sort clauses are specified.
     *
     * @since 6.12 (constant added in 6.7.6 and up)
     */
    public const CAPABILITY_SCORING = 1;

    /**
     * Capability flag for facets feature for use with {@see ::supports()}.
     *
     * Faceted search: https://en.wikipedia.org/wiki/Faceted_search
     *
     * Note: Even if search engine tells you this is supported, beware:
     * - It might not support all facets, by design it will only return facets for facet builders the search engine supports.
     * - Some of the faceting features are still work in progress in API and won't be further matured before in 7   .x
     *   releases
     *
     * @since 6.12 (constant added in 6.7.6 and up)
     */
    public const CAPABILITY_FACETS = 2;

    /**
     * Capability flag for custom fields feature for use with {@see ::supports()}.
     *
     * Custom fields is the capability for search engines to 1. allow you to extend the search index via plugins to
     * generate custom fields, like a different representation (format, ...) of an existing field or similar. And 2.
     * allow you on some search criteria to specify this custom field to rather query on that instead of the default
     * field generated by the system.
     *
     * @since 6.12 (constant added in 6.7.6 and up)
     */
    public const CAPABILITY_CUSTOM_FIELDS = 4;

    /**
     * Capability flag for spellcheck feature for use with {@see ::supports()}.
     *
     * Spell check within search capabilities refers to ability to suggest better wordings in fulltext search string.
     *
     * WARNING: This feature is considered experimental given it is not completely designed yet in terms of how it should
     * interact with FullText criterion (singular) which is the most relevant here. Also given how FullText can be part of a more complex criteria it
     * might imply a need to more strictly define where users are supposed to place FullText vs other criteria.
     *
     * @since 6.12 (constant added in 6.7.6 and up)
     */
    public const CAPABILITY_SPELLCHECK = 8;

    /**
     * Capability flag for highlight feature for use with {@see ::supports()}.
     *
     * Highlight in search refers to extracting relevant text from the search index that matches the search result,
     * typically returning a chunk of text of a predefined size with matching text highlighted.
     *
     * WARNING: This feature is considered experimental given it is not completely designed yet in terms of how it should
     * interact with hits within rich content of either eZ or custom field types. it is also unclear how it should
     * hint what part of the highlight is matched.
     *
     * @internal Maybe it should rather give just matched text and hint of which field (several: one with best score)
     * was matched and leave it to field type to render result with that info taking into account. But for now it is
     * designed as simple string field, so should be string with for instance `<mark>` around matched text.
     *
     * @since 6.12 (constant added in 6.7.6 and up)
     */
    public const CAPABILITY_HIGHLIGHT = 16;

    /**
     * Capability flag for suggest feature for use with {@see ::supports()}.
     *
     * WARNING: This feature is considered experimental given it is not completely clear what it is supposed to do. Feature
     * might be deprecated in the future.
     *
     * @since 6.12 (constant added in 6.7.6 and up)
     */
    public const CAPABILITY_SUGGEST = 32;

    /**
     * Capability flag for advanced fulltext feature for use with {@see ::supports()}.
     *
     * Advance full text is a feature making to possible by current engine to parse advance full text expressions.
     *
     * @since 6.12 (constant added in 6.7.6 and up)
     * @see \eZ\Publish\API\Repository\Values\Content\Query\Criterion\FullText
     */
    public const CAPABILITY_ADVANCED_FULLTEXT = 64;

    /**
     * Capability flag for aggregation feature for use with {@see ::supports()}.
     *
     * @since 8.2
     */
    public const CAPABILITY_AGGREGATIONS = 128;

    /**
     * Finds content objects for the given query.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if query is not valid
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param array $languageFilter Configuration for specifying prioritized languages query will be performed on.
     *        Also used to define which field languages are loaded for the returned content.
     *        Currently supports: <code>array("languages" => array(<language1>,..), "useAlwaysAvailable" => bool)</code>
     *                            useAlwaysAvailable defaults to true to avoid exceptions on missing translations
     * @param bool $filterOnUserPermissions if true only the objects which the user is allowed to read are returned.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Search\SearchResult
     */
    public function findContent(Query $query, array $languageFilter = [], bool $filterOnUserPermissions = true): SearchResult;

    /**
     * Finds contentInfo objects for the given query.
     *
     * This method works just like findContent, however does not load the full Content Objects. This means
     * it can be more efficient for use cases where you don't need the full Content. Also including use cases
     * where content will be loaded by separate code, like an ESI based sub requests that takes content ID as input.
     *
     * @since 5.4.5
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if query is not valid
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param array $languageFilter Configuration for specifying prioritized languages query will be performed on.
     *        Currently supports: <code>array("languages" => array(<language1>,..), "useAlwaysAvailable" => bool)</code>
     *                            useAlwaysAvailable defaults to true to avoid exceptions on missing translations
     * @param bool $filterOnUserPermissions if true (default) only the objects which is the user allowed to read are returned.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Search\SearchResult
     */
    public function findContentInfo(Query $query, array $languageFilter = [], bool $filterOnUserPermissions = true): SearchResult;

    /**
     * Performs a query for a single content object.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the object was not found by the query or due to permissions
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if criterion is not valid
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if there is more than than one result matching the criterions
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $filter
     * @param array $languageFilter Configuration for specifying prioritized languages query will be performed on.
     *        Currently supports: <code>array("languages" => array(<language1>,..), "useAlwaysAvailable" => bool)</code>
     *                            useAlwaysAvailable defaults to true to avoid exceptions on missing translations
     * @param bool $filterOnUserPermissions if true only the objects which is the user allowed to read are returned.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content
     */
    public function findSingle(Criterion $filter, array $languageFilter = [], bool $filterOnUserPermissions = true): Content;

    /**
     * Suggests a list of values for the given prefix.
     *
     * @param string $prefix
     * @param string[] $fieldPaths
     * @param int $limit
     * @param \eZ\Publish\API\Repository\Values\Content\Query\Criterion $filter
     */
    public function suggest(string $prefix, array $fieldPaths = [], int $limit = 10, Criterion $filter = null);

    /**
     * Finds Locations for the given query.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if query is not valid
     *
     * @param \eZ\Publish\API\Repository\Values\Content\LocationQuery $query
     * @param array $languageFilter Configuration for specifying prioritized languages query will be performed on.
     *        Currently supports: <code>array("languages" => array(<language1>,..), "useAlwaysAvailable" => bool)</code>
     *                            useAlwaysAvailable defaults to true to avoid exceptions on missing translations
     * @param bool $filterOnUserPermissions if true only the objects which is the user allowed to read are returned.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Search\SearchResult
     */
    public function findLocations(LocationQuery $query, array $languageFilter = [], bool $filterOnUserPermissions = true): SearchResult;

    /**
     * Query for supported capability of currently configured search engine.
     *
     * Will return false if search engine does not implement {@see \eZ\Publish\SPI\Search\Capable}.
     *
     * @since 6.12
     *
     * @param int $capabilityFlag One of CAPABILITY_* constants.
     *
     * @return bool
     */
    public function supports(int $capabilityFlag): bool;
}
