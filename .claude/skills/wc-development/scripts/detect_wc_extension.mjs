#!/usr/bin/env node

/**
 * WooCommerce Extension Detector
 *
 * Scans a WooCommerce extension project and returns a JSON report with:
 * - HPOS compatibility status
 * - Blocks integration status
 * - Gateway/shipping method detection
 * - Plugin headers
 * - Detected issues
 *
 * Usage: node detect_wc_extension.mjs /path/to/plugin
 */

import fs from 'fs';
import path from 'path';

const projectDir = process.argv[2] || '.';

if (!fs.existsSync(projectDir)) {
    console.error(`Directory not found: ${projectDir}`);
    process.exit(1);
}

const result = {
    is_wc_extension: false,
    headers: {},
    hpos: {
        declared: false,
        incompatible_patterns: [],
    },
    blocks: {
        declared: false,
        has_payment_registration: false,
        has_js_registration: false,
        has_store_api_extension: false,
    },
    gateways: [],
    shipping_methods: [],
    product_types: [],
    rest_endpoints: [],
    dependencies: {
        composer: false,
        npm: false,
        wp_scripts: false,
    },
    issues: [],
};

// Collect PHP files
function getPhpFiles(dir, files = []) {
    try {
        const entries = fs.readdirSync(dir, { withFileTypes: true });
        for (const entry of entries) {
            const fullPath = path.join(dir, entry.name);
            if (entry.isDirectory()) {
                if (['vendor', 'node_modules', '.git', 'build'].includes(entry.name)) continue;
                getPhpFiles(fullPath, files);
            } else if (entry.name.endsWith('.php')) {
                files.push(fullPath);
            }
        }
    } catch (e) { /* skip unreadable dirs */ }
    return files;
}

// Collect JS/TS files
function getJsFiles(dir, files = []) {
    try {
        const entries = fs.readdirSync(dir, { withFileTypes: true });
        for (const entry of entries) {
            const fullPath = path.join(dir, entry.name);
            if (entry.isDirectory()) {
                if (['vendor', 'node_modules', '.git', 'build', 'dist'].includes(entry.name)) continue;
                getJsFiles(fullPath, files);
            } else if (/\.(js|jsx|ts|tsx)$/.test(entry.name)) {
                files.push(fullPath);
            }
        }
    } catch (e) { /* skip */ }
    return files;
}

const phpFiles = getPhpFiles(projectDir);
const jsFiles = getJsFiles(projectDir);

// 1. Check plugin headers
for (const file of phpFiles) {
    try {
        const content = fs.readFileSync(file, 'utf-8').slice(0, 3000);
        const wcMatch = content.match(/WC requires at least:\s*(.+)/);
        if (wcMatch) {
            result.is_wc_extension = true;
            result.headers.wc_requires = wcMatch[1].trim();
        }
        const wcTestedMatch = content.match(/WC tested up to:\s*(.+)/);
        if (wcTestedMatch) result.headers.wc_tested = wcTestedMatch[1].trim();

        const versionMatch = content.match(/\*\s*Version:\s*(.+)/);
        if (versionMatch) result.headers.version = versionMatch[1].trim();

        const textDomainMatch = content.match(/Text Domain:\s*(.+)/);
        if (textDomainMatch) result.headers.text_domain = textDomainMatch[1].trim();

        const requiresPluginsMatch = content.match(/Requires Plugins:\s*(.+)/);
        if (requiresPluginsMatch) result.headers.requires_plugins = requiresPluginsMatch[1].trim();

        const wooHeaderMatch = content.match(/Woo:\s*(.+)/);
        if (wooHeaderMatch) result.headers.woo_marketplace_id = wooHeaderMatch[1].trim();
    } catch (e) { /* skip */ }
}

// 2. Scan PHP files for patterns
for (const file of phpFiles) {
    try {
        const content = fs.readFileSync(file, 'utf-8');
        const relPath = path.relative(projectDir, file);

        // HPOS declaration
        if (content.includes("custom_order_tables") && content.includes("declare_compatibility")) {
            result.hpos.declared = true;
        }

        // Blocks declaration
        if (content.includes("cart_checkout_blocks") && content.includes("declare_compatibility")) {
            result.blocks.declared = true;
        }

        // HPOS-incompatible patterns
        const incompatiblePatterns = [
            { pattern: /get_post_meta\s*\(\s*\$order/, desc: 'get_post_meta() on order' },
            { pattern: /update_post_meta\s*\(\s*\$order/, desc: 'update_post_meta() on order' },
            { pattern: /delete_post_meta\s*\(\s*\$order/, desc: 'delete_post_meta() on order' },
            { pattern: /get_post\s*\(\s*\$order/, desc: 'get_post() on order' },
            { pattern: /post_type\s*=\s*['"]shop_order['"]/, desc: 'Direct shop_order post_type query' },
            { pattern: /\$order->id[^_]/, desc: 'Deprecated $order->id access' },
            { pattern: /\$wpdb->posts.*shop_order/, desc: 'Direct wp_posts query for orders' },
            { pattern: /\$wpdb->postmeta.*order/, desc: 'Direct wp_postmeta query for orders' },
        ];
        for (const { pattern, desc } of incompatiblePatterns) {
            if (pattern.test(content)) {
                result.hpos.incompatible_patterns.push({ file: relPath, pattern: desc });
            }
        }

        // Payment gateways
        const gwMatch = content.match(/class\s+(\w+)\s+extends\s+WC_Payment_Gateway/);
        if (gwMatch) {
            result.gateways.push({ class: gwMatch[1], file: relPath });
        }

        // Shipping methods
        const shipMatch = content.match(/class\s+(\w+)\s+extends\s+WC_Shipping_Method/);
        if (shipMatch) {
            result.shipping_methods.push({ class: shipMatch[1], file: relPath });
        }

        // Product types
        const prodMatch = content.match(/class\s+(\w+)\s+extends\s+WC_Product[^_]/);
        if (prodMatch) {
            result.product_types.push({ class: prodMatch[1], file: relPath });
        }

        // Blocks payment registration (PHP)
        if (content.includes('PaymentMethodRegistry') || content.includes('payment_method_type_registration')) {
            result.blocks.has_payment_registration = true;
        }

        // Store API extension
        if (content.includes('register_endpoint_data') || content.includes('register_update_callback')) {
            result.blocks.has_store_api_extension = true;
        }

        // REST endpoints
        const restMatch = content.match(/register_rest_route\s*\(\s*['"]([^'"]+)['"]/);
        if (restMatch) {
            result.rest_endpoints.push({ namespace: restMatch[1], file: relPath });
        }
    } catch (e) { /* skip */ }
}

// 3. Scan JS files
for (const file of jsFiles) {
    try {
        const content = fs.readFileSync(file, 'utf-8');
        if (content.includes('registerPaymentMethod')) {
            result.blocks.has_js_registration = true;
        }
    } catch (e) { /* skip */ }
}

// 4. Check dependencies
result.dependencies.composer = fs.existsSync(path.join(projectDir, 'composer.json'));
result.dependencies.npm = fs.existsSync(path.join(projectDir, 'package.json'));

if (result.dependencies.npm) {
    try {
        const pkg = JSON.parse(fs.readFileSync(path.join(projectDir, 'package.json'), 'utf-8'));
        const allDeps = { ...pkg.dependencies, ...pkg.devDependencies };
        result.dependencies.wp_scripts = '@wordpress/scripts' in allDeps;
    } catch (e) { /* skip */ }
}

// 5. Check for changelog.txt
result.has_changelog = fs.existsSync(path.join(projectDir, 'changelog.txt'));

// 6. Detect issues
if (!result.hpos.declared) {
    result.issues.push({
        severity: 'error',
        message: 'HPOS compatibility declaration (custom_order_tables) not found. Required for WooCommerce 9.0+.',
    });
}
if (result.hpos.incompatible_patterns.length > 0) {
    result.issues.push({
        severity: 'warning',
        message: `${result.hpos.incompatible_patterns.length} HPOS-incompatible pattern(s) found (get_post_meta, WP_Query for orders, etc.).`,
    });
}
if (result.gateways.length > 0 && !result.blocks.declared) {
    result.issues.push({
        severity: 'warning',
        message: 'Payment gateway found but cart_checkout_blocks compatibility not declared.',
    });
}
if (result.gateways.length > 0 && !result.blocks.has_js_registration) {
    result.issues.push({
        severity: 'warning',
        message: 'Payment gateway found but no registerPaymentMethod JS implementation detected for block checkout.',
    });
}
if (result.gateways.length > 0 && !result.blocks.has_payment_registration) {
    result.issues.push({
        severity: 'warning',
        message: 'Payment gateway found but no PaymentMethodRegistry PHP registration detected.',
    });
}
if (!result.has_changelog) {
    result.issues.push({
        severity: 'info',
        message: 'changelog.txt not found. Required for WooCommerce.com Marketplace submissions.',
    });
}
if (!result.headers.requires_plugins) {
    result.issues.push({
        severity: 'info',
        message: '"Requires Plugins: woocommerce" header not found. Recommended for WordPress 6.5+.',
    });
}

console.log(JSON.stringify(result, null, 2));
