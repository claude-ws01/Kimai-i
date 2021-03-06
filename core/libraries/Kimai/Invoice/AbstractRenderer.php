<?php
/**
 * This file is part of
 * Kimai-i Open Source Time Tracking // https://github.com/claude-ws01/Kimai-i
 * (c) 2015 Claude Nadon  https://github.com/claude-ws01
 * (c) Kimai-Development-Team // http://www.kimai.org
 *
 * Kimai-i is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; Version 3, 29 June 2007
 *
 * Kimai-i is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kimai; If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Base class for rendering invoices.
 *
 */
abstract class Kimai_Invoice_AbstractRenderer
{
    /**
     * @var string
     */
    private $tempDir = null;
    /**
     * @var string
     */
    private $templateDir = null;
    /**
     * @var string
     */
    private $templateFile = null;
    /**
     * @var Kimai_Invoice_PrintModel
     */
    private $model = null;

    /**
     * Render the invoice.
     *
     * @return mixed
     */
    public abstract function render();

    /**
     * @param string $templateDir
     */
    public function setTemplateDir($templateDir)
    {
        $this->templateDir = $templateDir;
    }

    /**
     * @return string
     */
    public function getTemplateDir()
    {
        return $this->templateDir;
    }

    /**
     * @param string $templateFile
     */
    public function setTemplateFile($templateFile)
    {
        $this->templateFile = $templateFile;
    }

    /**
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->templateFile;
    }

    /**
     * @param \Kimai_Invoice_PrintModel $model
     */
    public function setModel(Kimai_Invoice_PrintModel $model)
    {
        $this->model = $model;
    }

    /**
     * @return \Kimai_Invoice_PrintModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param string $tempDir
     */
    public function setTemporaryDirectory($tempDir)
    {
        $this->tempDir = $tempDir;
    }

    /**
     * @return string
     */
    public function getTemporaryDirectory()
    {
        return $this->tempDir;
    }

    /**
     * Returns if the file can be rendered.
     *
     * @return bool
     */
    public function canRender()
    {
        return false;
    }
    
    protected function prepareCustomerArray($customer)
    {
        $new = array(
            'customerContact' => $customer['contact'],
            'companyName' => $customer['company'],
            'customerStreet' => $customer['street'],
            'customerCity' => $customer['city'],
            'customerZip' => $customer['zipcode'],
            'customerPhone' => $customer['phone'],
            'customerEmail' => $customer['mail'],
            'customerComment' => $customer['comment'],
            'customerFax' => $customer['fax'],
            'customerMobile' => $customer['mobile'],
            'customerURL' => $customer['homepage'],
            'customerVat' => $customer['vat_rate']
        );

        return $new;
    }

}
