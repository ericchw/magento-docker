<?php

declare(strict_types=1);

namespace Aeromalls\Storefront\Controller\Adminhtml\Image;

class Upload extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Aeromalls_Storefront::config';
    private \Magento\MediaStorage\Model\File\UploaderFactory $uploaders;
    private \Magento\Framework\Filesystem $filesystem;
    private \Magento\Framework\Controller\Result\JsonFactory $json;
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\MediaStorage\Model\File\UploaderFactory $uploaders, \Magento\Framework\Filesystem $filesystem, \Magento\Framework\Controller\Result\JsonFactory $json)
    {
        $this->uploaders = $uploaders;
        $this->filesystem = $filesystem;
        $this->json = $json;
        parent::__construct($context);
    }
    public function validateImage(string $file): void
    {
        $info = @getimagesize($file);
        if (!$info || !in_array($info['mime'] ?? '', ['image/jpeg','image/png','image/gif','image/webp'], true) || filesize($file) > 5 * 1024 * 1024) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Upload a raster image smaller than 5 MB.'));
        }
    }
    public function execute()
    {
        $result = $this->json->create()->setHeader('Cache-Control', 'private, no-store', true);
        try {
            $uploader = $this->uploaders->create(['fileId' => 'image']);
            $uploader->setAllowedExtensions(['jpg','jpeg','png','gif','webp'])->setAllowRenameFiles(true);
            $uploader->addValidateCallback('image', $this, 'validateImage');
            $directory = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
            $saved = $uploader->save($directory->getAbsolutePath('aeromalls/partners'));
            return $result->setData(['path' => 'aeromalls/partners/'.ltrim($saved['file'], '/')]);
        } catch (\Throwable $e) {
            return $result->setHttpResponseCode(400)->setData(['error' => (string)__('Unable to upload logo. Use a valid PNG, JPEG, GIF or WebP smaller than 5 MB.')]);
        }
    }
}
