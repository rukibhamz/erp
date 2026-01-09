<?php
/**
 * Customers Edit View
 * Alias for receivables/edit_customer
 */
defined('BASEPATH') OR exit('No direct script access allowed');
if (isset($customer['id'])) {
    redirect('receivables/editCustomer/' . $customer['id']);
}
redirect('receivables/customers');
