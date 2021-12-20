<?php

namespace RahulHaque\Filepond;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class Filepond extends AbstractFilepond
{
    /**
     * Set the FilePond field name
     *
     * @param string|array $field
     * @return $this
     */
    public function field($field)
    {
        $this->setFieldValue($field)
            ->setIsSoftDeletable(config('filepond.soft_delete', true))
            ->setFieldModel();

        return $this;
    }

    /**
     * Return file object from the field
     *
     * @return array|\Illuminate\Http\UploadedFile
     */
    public function getFile()
    {
        if (!$this->getFieldValue()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            return $this->getFieldModel()->map(function ($filepond) {
                return $this->createFileObject($filepond);
            })->toArray();
        }

        return $this->createFileObject($this->getFieldModel());
    }

    /**
     * Get the filepond database model for the FilePond field
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->getFieldModel();
    }

    /**
     * Copy the FilePond files to destination
     *
     * @param string $path
     * @return array
     */
    public function copyTo(string $path)
    {
        if (!$this->getFieldValue()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            $response = [];
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $index => $filepond) {
                $to = $path . '/' . time() . uniqid() . '-' . ($index + 1) . '.' . $filepond->extension;
                Storage::disk($filepond->disk)->copy($filepond->filepath, $to);
                $filePath = pathinfo($to);
                $response[] = array_merge(['id' => $filepond->id, 'file_url' => $filePath['dirname'] . '/' . $filePath['basename']], $filePath);;
            }
            return $response;
        }

        $filepond = $this->getFieldModel();
        $to = $path . '/' . time() . uniqid() . '.' . $filepond->extension;
        Storage::disk($filepond->disk)->copy($filepond->filepath, $to);
        $filePath = pathinfo($to);
        return array_merge(['id' => $filepond->id, 'file_url' => $filePath['dirname'] . '/' . $filePath['basename']], $filePath);
    }

    /**
     * Copy the FilePond files to destination and delete
     *
     * @param string $path
     * @return array
     */
    public function moveTo(string $path)
    {

        if (!$this->getFieldValue()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            $response = [];
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $index => $filepond) {
                $to = $path . '/' . time() . uniqid() . '-' . ($index + 1) . '.' . $filepond->extension;
                Storage::disk($filepond->disk)->move($filepond->filepath, $to);
                $filePath = pathinfo($to);
                $response[] = array_merge(['id' => $filepond->id, 'file_url' => $filePath['dirname'] . '/' . $filePath['basename']], $filePath);;
            }
            return $response;
        }

        $filepond = $this->getFieldModel();
        $to = $path . '/' . time() . uniqid() . '.' . $filepond->extension;
        Storage::disk($filepond->disk)->move($filepond->filepath, $to);
        $filePath = pathinfo($to);
        return array_merge(['id' => $filepond->id, 'file_url' => $filePath['dirname'] . '/' . $filePath['basename']], $filePath);
    }

    /**
     * Validate a file from temporary storage
     *
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return void
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(array $rules, array $messages = [], array $customAttributes = [])
    {
        $old = array_key_first($rules);
        $field = explode('.', $old)[0];

        if (!$this->getFieldValue() && ($old != $field)) {
            $rules[$field] = $rules[$old];
            unset($rules[$old]);
        }

        Validator::make([$field => $this->getFile()], $rules, $messages, $customAttributes)->validate();
    }

    /**
     * Delete files related to FilePond field
     *
     * @return void
     */
    public function delete()
    {
        if (!$this->getFieldValue()) {
            return null;
        }

        if ($this->getIsMultipleUpload()) {
            $fileponds = $this->getFieldModel();
            foreach ($fileponds as $filepond) {
                if ($this->getIsSoftDeletable()) {
                    $filepond->delete();
                } else {
                    Storage::disk($filepond->disk)->delete($filepond->filepath);
                    $filepond->forceDelete();
                }
            }
            return;
        }

        $filepond = $this->getFieldModel();
        if ($this->getIsSoftDeletable()) {
            $filepond->delete();
        } else {
            Storage::disk($filepond->disk)->delete($filepond->filepath);
            $filepond->forceDelete();
        }
    }
}
