<?php

$groups = [
    'Products' => ['Group' => 'Catalog', 'Sort' => 10],
    'Categories' => ['Group' => 'Catalog', 'Sort' => 20],
    'Brands' => ['Group' => 'Catalog', 'Sort' => 30],
    'Attributes' => ['Group' => 'Catalog', 'Sort' => 40],
    'Units' => ['Group' => 'Catalog', 'Sort' => 50],
    
    'Orders' => ['Group' => 'Sales', 'Sort' => 10],
    'Payments' => ['Group' => 'Sales', 'Sort' => 20],
    'ReturnRequests' => ['Group' => 'Sales', 'Sort' => 30],
    
    'Shipments' => ['Group' => 'Shipping', 'Sort' => 10],
    'Couriers' => ['Group' => 'Shipping', 'Sort' => 20],
    'CourierShipments' => ['Group' => 'Shipping', 'Sort' => 30],
    'ShippingMethods' => ['Group' => 'Shipping', 'Sort' => 40],
    
    'Customers' => ['Group' => 'Customers & Marketing', 'Sort' => 10],
    'Reviews' => ['Group' => 'Customers & Marketing', 'Sort' => 20],
    'Coupons' => ['Group' => 'Customers & Marketing', 'Sort' => 30],
    
    'Settings' => ['Group' => 'System', 'Sort' => 10],
];

foreach ($groups as $name => $data) {
    $group = $data['Group'];
    $sort = $data['Sort'];
    
    $file = __DIR__ . "/app/Filament/Resources/{$name}/" . (str_ends_with($name, 's') ? substr($name, 0, -1) : $name) . "Resource.php";
    if ($name === 'Categories') {
        $file = __DIR__ . "/app/Filament/Resources/Categories/CategoryResource.php";
    }
    
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    
    // Add/Update Group
    if (preg_match('/protected static string\|UnitEnum\|null \$navigationGroup = \'.*\';/', $content)) {
        $content = preg_replace('/protected static string\|UnitEnum\|null \$navigationGroup = \'.*\';/', "protected static \UnitEnum|string|null \$navigationGroup = '$group';", $content);
    } elseif (preg_match('/protected static \\\\UnitEnum\|string\|null \$navigationGroup = \'.*\';/', $content)) {
        $content = preg_replace('/protected static \\\\UnitEnum\|string\|null \$navigationGroup = \'.*\';/', "protected static \UnitEnum|string|null \$navigationGroup = '$group';", $content);
    } elseif (preg_match('/protected static \?string \$navigationGroup = \'.*\';/', $content)) {
        $content = preg_replace('/protected static \?string \$navigationGroup = \'.*\';/', "protected static \UnitEnum|string|null \$navigationGroup = '$group';", $content);
    } else {
        $content = preg_replace('/(protected static string\|BackedEnum\|null \$navigationIcon = [^;]+;)/', "$1\n    protected static \UnitEnum|string|null \$navigationGroup = '$group';", $content);
    }
    
    // Add/Update Sort
    if (preg_match('/protected static \?int \$navigationSort = \d+;/', $content)) {
        $content = preg_replace('/protected static \?int \$navigationSort = \d+;/', "protected static ?int \$navigationSort = $sort;", $content);
    } else {
        $content = preg_replace('/(protected static \\\\?UnitEnum\|string\|null \$navigationGroup = [^;]+;)/', "$1\n    protected static ?int \$navigationSort = $sort;", $content);
    }
    
    file_put_contents($file, $content);
    echo "Updated $name\n";
}
