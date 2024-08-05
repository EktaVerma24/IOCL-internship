<?php
// Configuration
$AD_SERVER = '10.19.64.100'; // Replace with your AD server IP or hostname
$AD_DOMAIN = 'IOC'; // Your AD domain
$AD_SEARCH_BASE = 'DS.INDIANOIL.IN'; // Correctly format the search base
$AD_PORT = isset($AD_PORT) ? $AD_PORT : 389; // Use default port 389 if not set

// LDAP connection
$ldapconn = ldap_connect("ldap://$AD_SERVER:$AD_PORT");

if (!$ldapconn) {
    die("Could not connect to LDAP server.");
}

// Set LDAP protocol version
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);

// Bind to LDAP server using domain credentials
$ldap_bind_dn = 'upso2admin@ioc.in'; // Replace with the actual domain username
$ldap_password = 'Noida@0542'; // Replace with the actual password
$ldapbind = ldap_bind($ldapconn, $ldap_bind_dn, $ldap_password);

if (!$ldapbind) {
    $error = ldap_error($ldapconn);
    die("Could not bind to LDAP server. Error: $error");
}

// Search for users in the AD
$search_filter = "(sAMAccountName=00029832)"; // Replace with the actual username or search criteria
$result = ldap_search($ldapconn, $AD_SEARCH_BASE, $search_filter);

if (!$result) {
    $error = ldap_error($ldapconn);
    die("LDAP search failed. Error: $error");
}

// Get entries
$entries = ldap_get_entries($ldapconn, $result);

// Display entries
echo "<pre>";
print_r($entries);
echo "</pre>";

// Close connection
ldap_close($ldapconn);
?>
