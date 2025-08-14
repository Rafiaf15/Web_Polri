import sys
import json
import re
from datetime import datetime

def extract_schedule_data(text):
    """
    Extract schedule data from various text formats including tables and formal letters
    """
    # Normalize whitespace and clean text
    text = text.replace('\r', ' ')
    text = text.replace('\n', ' ')
    text = re.sub(r'\s+', ' ', text).strip()
    
    debug_info = {
        "raw_text_length": len(text),
        "text_preview": text[:300] + "..." if len(text) > 300 else text
    }
    
    # Initialize result containers
    schedule_items = []
    
    # === FORMAT 1: Table format patterns ===
    table_patterns = [
        # Pattern: Day, Date | Time | Room | Activity
        r"(?P<day>Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu),?\s*(?P<date>\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4})\s*\|\s*(?P<time>\d{1,2}[.:]\d{2}[\s\-–]+\d{1,2}[.:]\d{2}(?:\s*WIB)?)\s*\|\s*(?P<room>[^|]+?)\s*\|\s*(?P<activity>.+?)(?=\n|$)",
        
        # Pattern: Date | Time | Room | Activity (without day)
        r"(?P<date>\d{2}/\d{2}/\d{4}|\d{1,2}\s+(?:Jan|Feb|Mar|Apr|Mei|Jun|Jul|Agu|Sep|Okt|Nov|Des)\s+\d{4})\s*\|\s*(?P<time>\d{1,2}[.:]\d{2}[\s\-–]+\d{1,2}[.:]\d{2}(?:\s*WIB)?)\s*\|\s*(?P<room>[^|]+?)\s*\|\s*(?P<activity>.+?)(?=\n|$)",
        
        # Pattern: Date Time Room Activity (space separated)
        r"(?P<date>\d{2}/\d{2}/\d{4})\s+(?P<time>\d{1,2}[.:]\d{2}[\s\-–]+\d{1,2}[.:]\d{2})\s+(?P<room>\S+(?:\s+\S+)*?)\s+(?P<activity>.+?)(?=\n|$)"
    ]
    
    # Extract from table patterns
    for pattern in table_patterns:
        for match in re.finditer(pattern, text, re.IGNORECASE | re.MULTILINE):
            item = {
                "day": match.groupdict().get("day", "").strip(),
                "date": match.group("date").strip(),
                "time": normalize_time(match.group("time").strip()),
                "location": match.group("room").strip(),
                "activity": match.group("activity").strip()
            }
            if item not in schedule_items:
                schedule_items.append(item)
    
    # === FORMAT 2: Formal letter format ===
    # Extract day and date combinations
    day_date_pattern = r"(Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu)\s*[,/]*\s*(\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4})"
    day_date_matches = re.findall(day_date_pattern, text, re.IGNORECASE)
    if not day_date_matches:
        day_date_pattern_alt = r"hari\s*/?\s*tanggal\s*:?[\s-]*?(Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu)\s*/\s*(\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4})"
        day_date_matches = re.findall(day_date_pattern_alt, text, re.IGNORECASE)
    
    # Extract time patterns
    time_patterns = [
        r"(?:pukul|pk|pkl|jam)\s*:?[\s-]*?(\d{1,2}[.:]\d{2})\s*WIB\s*(?:s\.?d\.?|sampai|hingga)\s*(?:selesai|(\d{1,2}[.:]\d{2})\s*WIB)",
        r"(\d{1,2}[.:]\d{2})\s*WIB\s*[\-–]\s*(\d{1,2}[.:]\d{2})\s*WIB",
        r"(\d{1,2}[.:]\d{2})[\s\-–]+(\d{1,2}[.:]\d{2})\s*WIB"
    ]
    
    time_matches = []
    for pattern in time_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE)
        for match in matches:
            if isinstance(match, tuple):
                if len(match) == 2 and match[1]:  # Both start and end time
                    time_matches.append(f"{match[0]} - {match[1]}")
                else:  # Only start time or "selesai"
                    time_matches.append(match[0] if match[0] else match[1])
            else:
                time_matches.append(match)
    
    # Extract location patterns
    location_patterns = [
        r"(?:bertempat\s+di|tempat|lokasi|ruang|ruangan)\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)",
        r"di\s+([A-Za-z0-9\s,\/\-\.()]+?)(?:\s+(?:pada|tanggal)|[.;]|\n|$)"
    ]
    
    location_matches = []
    for pattern in location_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE)
        for match in matches:
            clean_location = match.strip().rstrip('.,;')
            if clean_location and len(clean_location) > 2:
                location_matches.append(clean_location)
    
    # Extract activity patterns
    activity_patterns = [
        r"(?:perihal|hal|tentang|acara|kegiatan)\s*:?\s*(.+?)(?=[.;]|\n|$)",
        r"(?:mengundang|memohon|mengharap).*?(?:untuk|dalam|pada)\s+(.+?)(?=[.;]|\n|$)",
        r"Hal\s*:\s*(.+?)(?=[.;]|\n|$)"
    ]
    
    activity_matches = []
    for pattern in activity_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE)
        for match in matches:
            clean_activity = match.strip().rstrip('.,;')
            if clean_activity and len(clean_activity) > 3:
                activity_matches.append(clean_activity)
    
    # Combine formal letter extractions
    if day_date_matches or time_matches or location_matches or activity_matches:
        # Create combinations based on available data
        max_items = max(len(day_date_matches), len(time_matches), 
                       len(location_matches), len(activity_matches), 1)
        
        for i in range(max_items):
            day = day_date_matches[i][0] if i < len(day_date_matches) else ""
            date = day_date_matches[i][1] if i < len(day_date_matches) else ""
            time = normalize_time(time_matches[i]) if i < len(time_matches) else ""
            location = location_matches[i] if i < len(location_matches) else ""
            activity = activity_matches[i] if i < len(activity_matches) else ""
            
            # Only add if we have meaningful data
            if any([day, date, time, location, activity]):
                item = {
                    "day": day,
                    "date": date,
                    "time": time,
                    "location": location,
                    "activity": activity
                }
                if item not in schedule_items:
                    schedule_items.append(item)
    
    # === Legacy format for backward compatibility ===
    days = [item["day"] for item in schedule_items if item["day"]]
    dates_full = [item["date"] for item in schedule_items if item["date"]]
    times = [item["time"] for item in schedule_items if item["time"]]
    locations = [item["location"] for item in schedule_items if item["location"]]
    activities = [item["activity"] for item in schedule_items if item["activity"]]
    
    return {
        "schedule_items": schedule_items,  # New structured format
        # Legacy fields for backward compatibility
        "days": list(set(days)),  # Remove duplicates
        "dates": list(set(dates_full)),
        "times": list(set(times)),
        "locations": list(set(locations)),
        "activities": list(set(activities)),
        "raw_text_length": len(text),
        "extraction_success": len(schedule_items) > 0,
        "items_found": len(schedule_items),
        "debug": debug_info
    }

def normalize_time(time_str):
    """
    Normalize time format and handle common variations
    """
    if not time_str:
        return ""
    
    # Remove extra WIB mentions
    time_str = re.sub(r'\s*WIB\s*', ' WIB', time_str)
    time_str = re.sub(r'WIB\s+WIB', 'WIB', time_str)
    
    # Normalize separators
    time_str = re.sub(r'[.:]\s*', '.', time_str)
    time_str = re.sub(r'\s*[\-–]\s*', ' - ', time_str)
    
    # Handle "selesai" cases
    time_str = re.sub(r'\s*(?:s\.?d\.?|sampai|hingga)\s*selesai', ' - selesai', time_str)
    
    return time_str.strip()

def validate_extraction(result):
    """
    Validate extraction results and provide suggestions
    """
    validation = {
        "is_valid": False,
        "warnings": [],
        "suggestions": []
    }
    
    if result["items_found"] == 0:
        validation["warnings"].append("No schedule items found")
        validation["suggestions"].append("Check if the text contains recognizable date/time patterns")
    else:
        validation["is_valid"] = True
        
        # Check for incomplete items
        incomplete_items = [
            item for item in result["schedule_items"] 
            if not all([item["date"], item["time"], item["location"], item["activity"]])
        ]
        
        if incomplete_items:
            validation["warnings"].append(f"{len(incomplete_items)} items have missing fields")
            validation["suggestions"].append("Consider manual review for incomplete items")
    
    return validation

if __name__ == "__main__":
    try:
        text = sys.stdin.read()
        result = extract_schedule_data(text)
        validation = validate_extraction(result)
        
        # Add validation to result
        result["validation"] = validation
        
        print(json.dumps(result, ensure_ascii=False, indent=2))
    except Exception as e:
        error_result = {
            "error": str(e),
            "schedule_items": [],
            "extraction_success": False,
            "items_found": 0
        }
        print(json.dumps(error_result, ensure_ascii=False, indent=2))