<?php

namespace App\Entity;

use App\Model\Data\Data;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DataProcessingStatusRepository")
 */
class DataProcessingStatus
{
    /**
     * @var string
     * @ORM\Id()
     * @ORM\Column(type="uuid")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $requestId;

    /**
     * @var \DateTimeInterface
     * @ORM\Column(type="datetime")
     */
    private $addedAt;

    /**
     * @var string
     * @ORM\Column(type="string", length=50)
     */
    private $status;

    /**
     * @var string|resource
     * @ORM\Column(type="blob")
     */
    private $data;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $message;

    public function getDataUuid(): string
    {
        return $this->id;
    }

    public function setDataUuid(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function setRequestId(string $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }

    public function getAddedAt(): \DateTimeInterface
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeInterface $addedAt): self
    {
        $this->addedAt = $addedAt;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getData(): Data
    {
        return unserialize((string) gzuncompress(
            \is_resource($this->data) ? stream_get_contents($this->data) : $this->data
        ), [Data::class]);
    }

    public function setData(Data $data): self
    {
        $this->data = (string) gzcompress(serialize($data));

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = substr($message ?? '', 0, 255);

        return $this;
    }
}
