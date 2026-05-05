<?php

namespace App\Service;

use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AniListClient
{
    private const string ENDPOINT = 'https://graphql.anilist.co';

    private const string MEDIA_QUERY = <<<'GQL'
query ($page: Int, $perPage: Int, $sort: [MediaSort], $seasonYear: Int) {
  Page(page: $page, perPage: $perPage) {
    pageInfo { total currentPage lastPage hasNextPage perPage }
    media(type: ANIME, sort: $sort, seasonYear: $seasonYear) {
      id
      idMal
      title { romaji english native }
      description(asHtml: false)
      format
      status
      episodes
      duration
      countryOfOrigin
      isAdult
      source(version: 3)
      season
      seasonYear
      startDate { year month day }
      endDate { year month day }
      averageScore
      meanScore
      popularity
      favourites
      genres
      coverImage { extraLarge large color }
      bannerImage
      trailer { id site }
      studios { edges { isMain node { id name } } }
      characters(perPage: 12, sort: [ROLE, RELEVANCE]) {
        edges {
          role
          node { id name { full } image { large } gender }
          voiceActors(language: JAPANESE, sort: [RELEVANCE]) {
            id
            name { full }
            image { large }
            languageV2
          }
        }
      }
      staff(perPage: 8, sort: [RELEVANCE]) {
        edges {
          role
          node { id name { full } image { large } languageV2 }
        }
      }
    }
  }
}
GQL;

    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    /**
     * @return array{
     *   data: array{Page: array{
     *     pageInfo: array{total:int, currentPage:int, lastPage:int, hasNextPage:bool, perPage:int},
     *     media: array<int, array<string, mixed>>
     *   }},
     *   rateLimitRemaining: ?int
     * }
     */
    public function fetchPage(int $page, int $perPage = 50, string $sort = 'POPULARITY_DESC', ?int $seasonYear = null): array
    {
        $response = $this->httpClient->request('POST', self::ENDPOINT, [
            'json' => [
                'query'     => self::MEDIA_QUERY,
                'variables' => [
                    'page'       => $page,
                    'perPage'    => $perPage,
                    'sort'       => [$sort],
                    'seasonYear' => $seasonYear,
                ],
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'timeout' => 30,
        ]);

        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders(false);
        $remaining = isset($headers['x-ratelimit-remaining'][0])
            ? (int) $headers['x-ratelimit-remaining'][0]
            : null;

        if ($statusCode === 429) {
            $retryAfter = (int) ($headers['retry-after'][0] ?? 60);
            throw new RuntimeException("AniList rate limit hit; retry after {$retryAfter}s");
        }

        if ($statusCode >= 400) {
            throw new RuntimeException("AniList error {$statusCode}: " . $response->getContent(false));
        }

        $body = $response->toArray(false);

        if (isset($body['errors'])) {
            $msg = json_encode($body['errors']);
            throw new RuntimeException("AniList GraphQL errors: {$msg}");
        }

        return [
            'data'                => $body['data'] ?? [],
            'rateLimitRemaining'  => $remaining,
        ];
    }
}
