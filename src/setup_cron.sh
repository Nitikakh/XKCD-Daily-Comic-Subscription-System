#!/bin/bash

# XKCD Email System - CRON Job Setup Script
# This script sets up a CRON job to run cron.php every 24 hours at 9:00 AM

# Configuration variables
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_SCRIPT="$SCRIPT_DIR/cron.php"
CRON_TIME="0 9 * * *"  # Run daily at 9:00 AM
LOG_FILE="$SCRIPT_DIR/cron_setup.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to log messages
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
    echo -e "$1"
}

# Function to check if script is run as correct user
check_user() {
    if [[ $EUID -eq 0 ]]; then
        log_message "${YELLOW}Warning: Running as root. Consider running as a regular user.${NC}"
    fi
}

# Function to check if PHP is installed
check_php() {
    if ! command -v php &> /dev/null; then
        log_message "${RED}Error: PHP is not installed or not in PATH${NC}"
        exit 1
    fi
    
    PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2)
    log_message "${GREEN}PHP found: Version $PHP_VERSION${NC}"
}

# Function to check if cron.php exists
check_php_script() {
    if [[ ! -f "$PHP_SCRIPT" ]]; then
        log_message "${RED}Error: cron.php not found at $PHP_SCRIPT${NC}"
        exit 1
    fi
    
    log_message "${GREEN}Found PHP script: $PHP_SCRIPT${NC}"
}

# Function to test PHP script syntax
test_php_script() {
    log_message "${BLUE}Testing PHP script syntax...${NC}"
    
    if php -l "$PHP_SCRIPT" &> /dev/null; then
        log_message "${GREEN}PHP script syntax is valid${NC}"
    else
        log_message "${RED}Error: PHP script has syntax errors${NC}"
        php -l "$PHP_SCRIPT"
        exit 1
    fi
}

# Function to backup existing crontab
backup_crontab() {
    BACKUP_FILE="$SCRIPT_DIR/crontab_backup_$(date +%Y%m%d_%H%M%S).txt"
    
    if crontab -l &> /dev/null; then
        crontab -l > "$BACKUP_FILE"
        log_message "${GREEN}Existing crontab backed up to: $BACKUP_FILE${NC}"
    else
        log_message "${YELLOW}No existing crontab found${NC}"
    fi
}

# Function to check if cron job already exists
check_existing_cron() {
    if crontab -l 2>/dev/null | grep -q "$PHP_SCRIPT"; then
        log_message "${YELLOW}CRON job for this script already exists${NC}"
        
        echo -e "${BLUE}Existing CRON entries for this script:${NC}"
        crontab -l 2>/dev/null | grep "$PHP_SCRIPT"
        
        read -p "Do you want to replace the existing CRON job? (y/N): " -n 1 -r
        echo
        
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log_message "${YELLOW}Setup cancelled by user${NC}"
            exit 0
        fi
        
        # Remove existing entries
        (crontab -l 2>/dev/null | grep -v "$PHP_SCRIPT") | crontab -
        log_message "${GREEN}Removed existing CRON job${NC}"
    fi
}

# Function to add new cron job
add_cron_job() {
    # Create the cron job entry
    CRON_ENTRY="$CRON_TIME /usr/bin/php $PHP_SCRIPT >> $SCRIPT_DIR/cron_output.log 2>&1"
    
    # Add to crontab
    (crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
    
    if [[ $? -eq 0 ]]; then
        log_message "${GREEN}CRON job added successfully!${NC}"
        log_message "${GREEN}Schedule: Daily at 9:00 AM${NC}"
        log_message "${GREEN}Script: $PHP_SCRIPT${NC}"
        log_message "${GREEN}Output log: $SCRIPT_DIR/cron_output.log${NC}"
    else
        log_message "${RED}Error: Failed to add CRON job${NC}"
        exit 1
    fi
}

# Function to verify cron job was added
verify_cron_job() {
    log_message "${BLUE}Verifying CRON job installation...${NC}"
    
    if crontab -l 2>/dev/null | grep -q "$PHP_SCRIPT"; then
        log_message "${GREEN}✓ CRON job verified successfully${NC}"
        
        echo -e "\n${BLUE}Current CRON jobs:${NC}"
        crontab -l 2>/dev/null | grep -n "$PHP_SCRIPT"
    else
        log_message "${RED}✗ CRON job verification failed${NC}"
        exit 1
    fi
}

# Function to test the cron job manually
test_cron_job() {
    read -p "Do you want to test the CRON job now? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        log_message "${BLUE}Running test execution...${NC}"
        
        # Run the PHP script manually
        php "$PHP_SCRIPT"
        
        if [[ $? -eq 0 ]]; then
            log_message "${GREEN}✓ Test execution completed successfully${NC}"
        else
            log_message "${RED}✗ Test execution failed${NC}"
        fi
    fi
}

# Function to show next run time
show_next_run() {
    # Calculate next run time (9 AM tomorrow or today if before 9 AM)
    CURRENT_HOUR=$(date +%H)
    
    if [[ $CURRENT_HOUR -lt 9 ]]; then
        NEXT_RUN=$(date -d "today 09:00" '+%Y-%m-%d %H:%M:%S')
    else
        NEXT_RUN=$(date -d "tomorrow 09:00" '+%Y-%m-%d %H:%M:%S')
    fi
    
    log_message "${BLUE}Next scheduled run: $NEXT_RUN${NC}"
}

# Function to create monitoring script
create_monitoring_script() {
    MONITOR_SCRIPT="$SCRIPT_DIR/monitor_cron.sh"
    
    cat > "$MONITOR_SCRIPT" << 'EOF'
#!/bin/bash
# XKCD CRON Job Monitor Script

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="$SCRIPT_DIR/cron_output.log"

echo "=== XKCD CRON Job Status ==="
echo "Last 10 log entries:"
echo "========================"

if [[ -f "$LOG_FILE" ]]; then
    tail -n 10 "$LOG_FILE"
else
    echo "No log file found at $LOG_FILE"
fi

echo ""
echo "CRON job status:"
if crontab -l 2>/dev/null | grep -q "cron.php"; then
    echo "✓ CRON job is installed"
    crontab -l 2>/dev/null | grep "cron.php"
else
    echo "✗ CRON job not found"
fi
EOF

    chmod +x "$MONITOR_SCRIPT"
    log_message "${GREEN}Created monitoring script: $MONITOR_SCRIPT${NC}"
}

# Function to display usage information
show_usage() {
    echo "XKCD Email System - CRON Setup Script"
    echo "======================================"
    echo "This script sets up a daily CRON job to send XKCD comics via email."
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -h, --help     Show this help message"
    echo "  -t, --time     Set custom time (default: 0 9 * * * for 9 AM)"
    echo "  -r, --remove   Remove existing CRON job"
    echo "  -s, --status   Show current CRON job status"
    echo ""
    echo "Examples:"
    echo "  $0                    # Set up CRON job with default settings"
    echo "  $0 -t '0 8 * * *'     # Set up CRON job for 8 AM"
    echo "  $0 --remove           # Remove existing CRON job"
    echo "  $0 --status           # Check current status"
}

# Function to remove cron job
remove_cron_job() {
    if crontab -l 2>/dev/null | grep -q "$PHP_SCRIPT"; then
        backup_crontab
        (crontab -l 2>/dev/null | grep -v "$PHP_SCRIPT") | crontab -
        log_message "${GREEN}CRON job removed successfully${NC}"
    else
        log_message "${YELLOW}No CRON job found to remove${NC}"
    fi
}

# Function to show status
show_status() {
    echo "=== XKCD CRON Job Status ==="
    
    if crontab -l 2>/dev/null | grep -q "$PHP_SCRIPT"; then
        echo -e "${GREEN}✓ CRON job is installed${NC}"
        echo "Current entries:"
        crontab -l 2>/dev/null | grep "$PHP_SCRIPT"
    else
        echo -e "${RED}✗ No CRON job found${NC}"
    fi
    
    if [[ -f "$SCRIPT_DIR/cron_output.log" ]]; then
        echo ""
        echo "Last execution log:"
        tail -n 5 "$SCRIPT_DIR/cron_output.log"
    fi
}

# Main execution function
main() {
    log_message "${BLUE}Starting XKCD CRON job setup...${NC}"
    
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_usage
                exit 0
                ;;
            -t|--time)
                CRON_TIME="$2"
                shift 2
                ;;
            -r|--remove)
                remove_cron_job
                exit 0
                ;;
            -s|--status)
                show_status
                exit 0
                ;;
            *)
                log_message "${RED}Unknown option: $1${NC}"
                show_usage
                exit 1
                ;;
        esac
    done
    
    # Run setup steps
    check_user
    check_php
    check_php_script
    test_php_script
    backup_crontab
    check_existing_cron
    add_cron_job
    verify_cron_job
    show_next_run
    create_monitoring_script
    test_cron_job
    
    echo ""
    log_message "${GREEN}=== Setup Complete ===${NC}"
    log_message "${GREEN}Your XKCD email system is now scheduled to run daily at 9:00 AM${NC}"
    log_message "${BLUE}Use '$SCRIPT_DIR/monitor_cron.sh' to check status${NC}"
    log_message "${BLUE}Logs will be written to: $SCRIPT_DIR/cron_output.log${NC}"
}

# Run main function with all arguments
main "$@"
