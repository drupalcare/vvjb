<?php

declare(strict_types=1);

namespace Drupal\vvjb\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\vvj_core\Service\TokenResolver;
use Drupal\vvjb\Plugin\views\style\BasicCarousel;

/**
 * Token hooks — exposes per-row field tokens for VVJB-enabled Views.
 *
 * Token forms:
 *   [vvjb:FIELD]        — rendered HTML
 *   [vvjb:FIELD:plain]  — plain text.
 */
final class VvjbTokenHooks {

  use StringTranslationTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\vvj_core\Service\TokenResolver|null $tokenResolver
   *   Shared token resolver. Nullable to keep the container compilable
   *   during the v1 → v2 upgrade window when vvj_core is on disk but
   *   not yet enabled. vvjb_update_10001 installs it; subsequent
   *   container rebuilds inject the real service.
   */
  public function __construct(
    private readonly ?TokenResolver $tokenResolver,
  ) {}

  /**
   * Implements hook_token_info().
   *
   * @return array<string, array<string, array<string, mixed>>>
   *   Token type + token definitions for the `vvjb` namespace.
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    return [
      'types' => [
        'vvjb' => [
          'name' => $this->t('VVJB Basic Carousel'),
          'description' => $this->t('Tokens for accessing field values from the first row of a VVJB-enabled View.'),
          'needs-data' => 'view',
        ],
      ],
      'tokens' => [
        'vvjb' => [
          '*' => [
            'name' => $this->t('Field token'),
            'description' => $this->t('Use [vvjb:FIELD_NAME] for HTML output or [vvjb:FIELD_NAME:plain] for plain text.'),
          ],
        ],
      ],
    ];
  }

  /**
   * Implements hook_tokens().
   *
   * @param string $type
   *   Token type — only `vvjb` is handled.
   * @param array<string, string> $tokens
   *   Token name → original placeholder map.
   * @param array<string, mixed> $data
   *   Token data; expects `view` key with a ViewExecutable.
   * @param array<string, mixed> $options
   *   Token resolution options.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   For propagating cache metadata.
   *
   * @return array<string, string|\Drupal\Component\Render\MarkupInterface>
   *   Map of original placeholder → resolved value.
   */
  #[Hook('tokens')]
  public function tokens(
    string $type,
    array $tokens,
    array $data,
    array $options,
    BubbleableMetadata $bubbleable_metadata,
  ): array {
    if ($type !== 'vvjb' || $this->tokenResolver === NULL) {
      return [];
    }
    return $this->tokenResolver->resolve(
      'vvjb',
      $tokens,
      $data,
      $bubbleable_metadata,
      BasicCarousel::class,
    );
  }

}
