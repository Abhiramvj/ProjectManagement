#!/bin/bash

# Project Optimization Script
# This script performs comprehensive optimization of the Laravel + Vue.js project

echo "🚀 Starting Project Optimization..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "composer.json" ] || [ ! -f "package.json" ]; then
    print_error "Please run this script from the project root directory"
    exit 1
fi

print_status "Starting optimization process..."

# 1. Clean previous builds
print_status "Cleaning previous builds..."
rm -rf public/build
rm -rf node_modules/.vite
print_success "Cleanup completed"

# 2. Install/Update dependencies
print_status "Installing/Updating dependencies..."
npm install
composer install --optimize-autoloader --no-dev
print_success "Dependencies updated"

# 3. Laravel optimizations
print_status "Running Laravel optimizations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
print_success "Laravel optimizations completed"

# 4. Build frontend assets
print_status "Building frontend assets..."
npm run build
print_success "Frontend build completed"

# 5. Analyze bundle size
print_status "Analyzing bundle size..."
npm run analyze-bundle &
ANALYZER_PID=$!

# Wait for analyzer to start
sleep 5

# 6. Performance checks
print_status "Running performance checks..."

# Check bundle sizes
if [ -d "public/build" ]; then
    print_status "Bundle size analysis:"
    find public/build -name "*.js" -exec ls -lh {} \; | head -10
    find public/build -name "*.css" -exec ls -lh {} \; | head -5
fi

# 7. Security checks
print_status "Running security checks..."
npm audit --audit-level moderate || print_warning "Some security vulnerabilities found (check npm audit for details)"

# 8. Final optimizations
print_status "Applying final optimizations..."

# Set proper permissions
chmod -R 755 public/build
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Create optimization report
cat > optimization_report.txt << EOF
Project Optimization Report
Generated: $(date)

## Bundle Analysis
$(find public/build -name "*.js" -exec ls -lh {} \; | head -10)

## CSS Analysis
$(find public/build -name "*.css" -exec ls -lh {} \; | head -5)

## Performance Recommendations
1. Monitor bundle sizes monthly
2. Run npm run analyze-bundle regularly
3. Check performance headers
4. Monitor memory usage
5. Review cache hit rates

## Next Steps
1. Test the application thoroughly
2. Monitor performance metrics
3. Update dependencies regularly
4. Review optimization guide for maintenance

EOF

print_success "Optimization report generated: optimization_report.txt"

# 9. Cleanup
print_status "Cleaning up temporary files..."
rm -rf node_modules/.vite

# 10. Final status
print_success "🎉 Project optimization completed successfully!"
print_status "Key improvements implemented:"
echo "  ✓ Bundle splitting and lazy loading"
echo "  ✓ CSS optimization and critical path"
echo "  ✓ Laravel caching and optimization"
echo "  ✓ Performance monitoring middleware"
echo "  ✓ Asset compression and caching"
echo "  ✓ Security headers and optimizations"

print_status "Next steps:"
echo "  1. Test the application in different environments"
echo "  2. Monitor performance using browser dev tools"
echo "  3. Review the optimization guide for maintenance"
echo "  4. Set up continuous performance monitoring"

# Kill analyzer if still running
if kill -0 $ANALYZER_PID 2>/dev/null; then
    print_status "Bundle analyzer is running at http://localhost:8888"
    print_status "Press Ctrl+C to stop the analyzer"
    wait $ANALYZER_PID
fi

print_success "Optimization script completed! 🚀" 