<?php
namespace Stack\Lib;

/**
 * Classe para requisições de arquivos
 * @package Stack\Lib
 */
class FileRequest {

    public $name = '';
    public $type = '';
    public $tmp_name = '';
    public $error = false;
    public $size = 0;
    public $ext = '';
    public $data = null;

    /**
     * @param array $file
     */
    public function __construct(array $file) {
        $this->name = $file['name'];
        $this->type = $file['type'];
        $this->size = $file['size'];
        $this->tmp_name = $file['tmp_name'] ?? null;
        $this->error = $file['error'] ?? false;
        $this->data = $file['data'] ?? null;
        $this->ext = array_slice(explode('.', $this->name), -1)[0];
    }

    /**
     * Check file type
     *
     * @param $type
     * @return bool
     */
    public function is($type) {
        $type = preg_quote($type, '@');
        return (bool) preg_match("@$type@", ($this->type ?? ''));
    }

    /**
     * Read the file
     *
     * @return false|string
     */
    public function read() {
        if(! $this->tmp_name) {
            return $this->data;
        }
        return file_get_contents($this->tmp_name);
    }

    /**
     * Move file to a folder
     *
     * @param string $to
     * @return bool
     */
    public function move(string $to) {
        if(! $this->tmp_name) {
            return file_put_contents($to, $this->data);
        }
        return move_uploaded_file($this->tmp_name, $to);
    }

    /**
     * Get file from encoded base64
     *
     * @param string $base64
     * @param string $file_name
     * @return FileRequest
     */
    public static function fromBase64(string $base64, string $file_name) {
        $parts = preg_split('@(,|:|;)@', $base64);
        $content = base64_decode($parts[3]);

        return new self([
            'name' => $file_name,
            'type' => $parts[1],
            'data' => $content,
            'size' => strlen($content)
        ]);
    }
}