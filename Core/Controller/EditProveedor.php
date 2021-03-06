<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Dinamic\Lib\IDFiscal;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\TotalModel;

/**
 * Controller to edit a single item from the Proveedor model
 *
 * @author Nazca Networks               <comercial@nazcanetworks.com>
 * @author Fco. Antonio Moreno Pérez    <famphuelva@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class EditProveedor extends ExtendedController\EditController
{

    /**
     * Returns the sum of the customer's total delivery notes.
     *
     * @return string
     */
    public function calcSupplierDeliveryNotes()
    {
        $where = [
            new DataBaseWhere('codproveedor', $this->getViewModelValue('EditProveedor', 'codproveedor')),
            new DataBaseWhere('editable', true)
        ];

        $totalModel = TotalModel::all('albaranesprov', $where, ['total' => 'SUM(total)'], '')[0];
        $divisaTools = new DivisaTools();
        return $divisaTools->format($totalModel->totals['total'], 2);
    }

    /**
     * Returns the sum of the client's total outstanding invoices.
     *
     * @return string
     */
    public function calcSupplierInvoicePending()
    {
        $where = [
            new DataBaseWhere('codproveedor', $this->getViewModelValue('EditProveedor', 'codproveedor')),
            new DataBaseWhere('pagado', false)
        ];

        $totalModel = TotalModel::all('facturasprov', $where, ['total' => 'SUM(total)'], '')[0];
        $divisaTools = new DivisaTools();
        return $divisaTools->format($totalModel->totals['total'], 2);
    }

    /**
     * 
     * @return string
     */
    public function getModelClassName()
    {
        return 'Proveedor';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'supplier';
        $pagedata['icon'] = 'fas fa-users';
        $pagedata['menu'] = 'purchases';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * 
     * @param string $name
     * @param string $model
     * @param string $label
     * @param string $icon
     */
    protected function createContactsView($name, $model, $label, $icon)
    {
        $this->addListView($name, $model, $label, $icon);

        /// sort options
        $this->views[$name]->addOrderBy(['fechaalta'], 'date');
        $this->views[$name]->addOrderBy(['descripcion'], 'descripcion', 2);

        /// search columns
        $this->views[$name]->searchFields[] = 'apellidos';
        $this->views[$name]->searchFields[] = 'descripcion';
        $this->views[$name]->searchFields[] = 'direccion';
        $this->views[$name]->searchFields[] = 'email';
        $this->views[$name]->searchFields[] = 'nombre';
    }

    /**
     * 
     * @param string $name
     * @param string $model
     * @param string $label
     */
    protected function createLineView($name, $model, $label)
    {
        $this->addListView($name, $model, $label, 'fas fa-cubes');

        /// sort options
        $this->views[$name]->addOrderBy(['idlinea'], 'code', 2);
        $this->views[$name]->addOrderBy(['cantidad'], 'quantity');
        $this->views[$name]->addOrderBy(['pvptotal'], 'amount');

        /// search columns
        $this->views[$name]->searchFields[] = 'referencia';
        $this->views[$name]->searchFields[] = 'descripcion';

        /// Disable buttons
        $this->setSettings($name, 'btnDelete', false);
        $this->setSettings($name, 'btnNew', false);
    }

    /**
     * 
     * @param string $name
     * @param string $model
     * @param string $label
     */
    protected function createListView($name, $model, $label)
    {
        $this->addListView($name, $model, $label, 'fas fa-copy');

        /// sort options
        $this->views[$name]->addOrderBy(['codigo'], 'code');
        $this->views[$name]->addOrderBy(['fecha', 'hora'], 'date', 2);
        $this->views[$name]->addOrderBy(['numero'], 'number');
        $this->views[$name]->addOrderBy(['numproveedor'], 'numsupplier');
        $this->views[$name]->addOrderBy(['total'], 'amount');

        /// search columns
        $this->views[$name]->searchFields[] = 'numproveedor';
        $this->views[$name]->searchFields[] = 'observaciones';

        /// Disable columns
        $this->views[$name]->disableColumn('customer', true);
    }

    /**
     * 
     * @param string $name
     * @param string $model
     * @param string $label
     * @param string $icon
     */
    protected function createSubaccountsView($name, $model, $label, $icon)
    {
        $this->addListView($name, $model, $label, $icon);

        /// sort options
        $this->views[$name]->addOrderBy(['codigo'], 'code');
        $this->views[$name]->addOrderBy(['codejercicio'], 'exercise', 2);
        $this->views[$name]->addOrderBy(['descripcion'], 'descripcion');
        $this->views[$name]->addOrderBy(['saldo'], 'balance');

        /// search columns
        $this->views[$name]->searchFields[] = 'codigo';
        $this->views[$name]->searchFields[] = 'description';

        /// Disable buttons
        $this->setSettings('ListSubcuenta', 'btnNew', false);
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createContactsView('ListContacto', 'Contacto', 'addresses-and-contacts', 'fas fa-address-book');
        $this->addEditListView('EditCuentaBancoProveedor', 'CuentaBancoProveedor', 'bank-accounts', 'fas fa-piggy-bank');
        $this->createSubaccountsView('ListSubcuenta', 'Subcuenta', 'subaccounts', 'fas fa-book');

        $this->createListView('ListFacturaProveedor', 'FacturaProveedor', 'invoices');
        $this->createLineView('ListLineaFacturaProveedor', 'LineaFacturaCliente', 'products');
        $this->createListView('ListAlbaranProveedor', 'AlbaranProveedor', 'delivery-notes');
        $this->createListView('ListPedidoProveedor', 'PedidoProveedor', 'orders');
        $this->createListView('ListPresupuestoProveedor', 'PresupuestoProveedor', 'estimations');
    }

    /**
     * Load view data
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        $codproveedor = $this->getViewModelValue('EditProveedor', 'codproveedor');
        switch ($viewName) {
            case 'EditProveedor':
                parent::loadData($viewName, $view);
                $this->setCustomWidgetValues();
                break;

            case 'EditCuentaBancoProveedor':
            case 'ListAlbaranProveedor':
            case 'ListContacto':
            case 'ListFacturaProveedor':
            case 'ListPedidoProveedor':
            case 'ListPresupuestoProveedor':
                $where = [new DataBaseWhere('codproveedor', $codproveedor)];
                $view->loadData('', $where);
                break;

            case 'ListLineaFacturaProveedor':
                $inSQL = 'SELECT idfactura FROM facturasprov WHERE codproveedor = ' . $this->dataBase->var2str($codproveedor);
                $where = [new DataBaseWhere('idfactura', $inSQL, 'IN')];
                $view->loadData('', $where);
                break;

            case 'ListSubcuenta':
                $codsubcuenta = $this->getViewModelValue('EditProveedor', 'codsubcuenta');
                $where = [new DataBaseWhere('codsubcuenta', $codsubcuenta)];
                $view->loadData('', $where);
                break;
        }
    }

    protected function setCustomWidgetValues()
    {
        /// Search for supplier contacts
        $codproveedor = $this->getViewModelValue('EditProveedor', 'codproveedor');
        $where = [new DataBaseWhere('codproveedor', $codproveedor)];
        $contacts = $this->codeModel->all('contactos', 'idcontacto', 'descripcion', false, $where);

        /// Load values option to default contact
        $columnBilling = $this->views['EditProveedor']->columnForName('contact');
        $columnBilling->widget->setValuesFromCodeModel($contacts);

        /// Load values option to Fiscal ID select input
        $columnFiscalID = $this->views['EditProveedor']->columnForName('fiscal-id');
        $columnFiscalID->widget->setValuesFromArray(IDFiscal::all());

        /// Load values option to VAT Type select input
        $columnVATType = $this->views['EditProveedor']->columnForName('vat-regime');
        $columnVATType->widget->setValuesFromArrayKeys(RegimenIVA::all());
    }
}
