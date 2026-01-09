<?php
/**
 * Customers View View
 * Alias for receivables/view_customer
 */
defined('BASEPATH') OR exit('No direct script access allowed');
if (isset($customer['id'])) {
    redirect('receivables/viewCustomer/' . $customer['id']);
}
redirect('receivables/customers');
