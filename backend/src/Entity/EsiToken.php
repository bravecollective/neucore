<?php

declare(strict_types=1);

namespace Neucore\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="esi_tokens",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="character_eve_login_idx", columns={"character_id", "eve_login_id"})
 *     }
 * )
 */
class EsiToken
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var integer|null
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Character", inversedBy="esiTokens")
     * @ORM\JoinColumn(nullable=false, name="character_id", onDelete="CASCADE")
     * @var Character|null
     */
    private $character;

    /**
     * @ORM\ManyToOne(targetEntity="EveLogin", inversedBy="esiTokens")
     * @ORM\JoinColumn(nullable=false, name="eve_login_id")
     * @var EveLogin|null
     */
    private $eveLogin;

    /**
     * The OAuth refresh token.
     *
     * Empty string if the token became invalid.
     *
     * @ORM\Column(type="text", length=65535, name="refresh_token")
     * @var string|null
     */
    private $refreshToken;

    /**
     * @ORM\Column(type="text", length=65535, name="access_token")
     * @var string|null
     */
    private $accessToken;

    /**
     * Shows if the refresh token is valid or not.
     *
     * This is null if there is no refresh token (EVE SSOv1 only)
     * or a valid token but without scopes (SSOv2).
     *
     * @ORM\Column(type="boolean", name="valid_token", nullable=true)
     * @var bool|null
     */
    private $validToken;

    /**
     * Date and time when that valid token property was last changed.
     *
     * @ORM\Column(type="datetime", name="valid_token_time", nullable=true)
     * @var \DateTime|null
     */
    private $validTokenTime;

    /**
     * Unix timestamp when access token expires.
     *
     * @ORM\Column(type="integer")
     * @var int|null
     */
    private $expires;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCharacter(Character $character): self
    {
        $this->character = $character;
        return $this;
    }

    public function getCharacter(): ?Character
    {
        return $this->character;
    }

    public function setEveLogin(EveLogin $eveLogin): self
    {
        $this->eveLogin = $eveLogin;
        return $this;
    }

    public function getEveLogin(): ?EveLogin
    {
        return $this->eveLogin;
    }

    public function setRefreshToken(string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setExpires(int $expires): self
    {
        $this->expires = $expires;
        return $this;
    }

    public function getExpires(): ?int
    {
        return $this->expires;
    }

    /**
     * Set validToken and updates validTokenTime.
     */
    public function setValidToken(?bool $validToken = null): self
    {
        if ($this->validToken !== $validToken) {
            try {
                $this->validTokenTime = new \DateTime();
            } catch (\Exception $e) {
                // ignore
            }
        }
        $this->validToken = $validToken;
        return $this;
    }

    public function getValidToken(): ?bool
    {
        return $this->validToken;
    }

    public function setValidTokenTime(\DateTime $validTokenTime): self
    {
        $this->validTokenTime = clone $validTokenTime;
        return $this;
    }

    public function getValidTokenTime(): ?\DateTime
    {
        return $this->validTokenTime;
    }
}
