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
    
    # === FORMAT 7: Complete location extraction ===
    # Look for complete location patterns
    complete_location_patterns = [
        # Pattern for "Via Zoom Meeting di Command Center/Media Center satker masing-masing"
        r"[Tt]empat\s*:?\s*Via\s+([A-Za-z0-9\s,\/\-\.()]+?)\s+di\s+([A-Za-z0-9\s,\/\-\.()\/]+?)\s+satker\s+masing-masing",
        # Pattern for "Via Zoom Meeting di Command Center/Media Center"
        r"[Tt]empat\s*:?\s*Via\s+([A-Za-z0-9\s,\/\-\.()]+?)\s+di\s+([A-Za-z0-9\s,\/\-\.()\/]+?)(?=\s+satker|[.;]|\n|$)"
    ]
    
    for pattern in complete_location_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE)
        for match in matches:
            if len(match) >= 2:
                # Create a complete location string
                if "satker masing-masing" in text.lower():
                    complete_location = f"Via {match[0].strip()} di {match[1].strip()} satker masing-masing"
                else:
                    complete_location = f"Via {match[0].strip()} di {match[1].strip()}"
                
                # Look for existing items to update
                for item in schedule_items:
                    if not item["location"] or len(item["location"]) < len(complete_location):
                        item["location"] = complete_location
                        break
                else:
                    # If no existing item found, create a new one with basic info
                    day_date_match = re.search(r"(Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu)\s*[,/]\s*(\d{1,2}\s+[A-Za-z]+\s+\d{4})", text, re.IGNORECASE)
                    if day_date_match:
                        day = day_date_match.group(1).strip()
                        date = day_date_match.group(2).strip()
                        
                        # Look for time
                        time_match = re.search(r"(?:Pk|Pukul|Jam)\s*\.?\s*(\d{1,2}[.:]\d{2})\s*WIB", text, re.IGNORECASE)
                        time = f"{time_match.group(1)} - selesai" if time_match else ""
                        
                        # Look for activity
                        activity_match = re.search(r"[Hh]al\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)", text, re.IGNORECASE)
                        activity = activity_match.group(1).strip() if activity_match else ""
                        
                        item = {
                            "day": day,
                            "date": date,
                            "time": time,
                            "location": complete_location,
                            "activity": activity
                        }
                        if item not in schedule_items:
                            schedule_items.append(item)
    
    # === FORMAT 3: Mahkamah Agung specific format ===
    # Look for specific patterns used in MA letters
    ma_pattern = r"[Hh]ari\s*/?\s*[Tt]anggal\s*:?\s*([A-Za-z]+)\s*[,/]\s*(\d{1,2}\s+[A-Za-z]+\s+\d{4}).*?[Ww]aktu\s*:?\s*([A-Za-z0-9\s\.\-:]+?)(?=\n|[.;]|$)"
    ma_matches = re.findall(ma_pattern, text, re.IGNORECASE | re.DOTALL)
    
    for match in ma_matches:
        if len(match) >= 3:
            day = match[0].strip()
            date = match[1].strip()
            time = normalize_time(match[2].strip())
            
            # Extract location and activity from the same context
            context_start = text.find(match[2]) + len(match[2])
            context_end = text.find('\n', context_start)
            if context_end == -1:
                context_end = len(text)
            context = text[context_start:context_end]
            
            # Look for location in context
            location = ""
            location_patterns = [
                r"[Tt]empat\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)",
                r"di\s+([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)",
                r"Via\s+([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)"
            ]
            
            for loc_pattern in location_patterns:
                loc_match = re.search(loc_pattern, context, re.IGNORECASE)
                if loc_match:
                    location = loc_match.group(1).strip()
                    break
            
            # If location is still empty, try to find it in the full text
            if not location:
                # Look for the full location pattern
                full_loc_patterns = [
                    r"[Tt]empat\s*:?\s*Via\s+([A-Za-z0-9\s,\/\-\.()]+?)\s+di\s+([A-Za-z0-9\s,\/\-\.()\/]+?)(?=\s+satker|[.;]|\n|$)",
                    r"Via\s+([A-Za-z0-9\s,\/\-\.()]+?)\s+di\s+([A-Za-z0-9\s,\/\-\.()\/]+?)(?=\s+satker|[.;]|\n|$)",
                    r"[Tt]empat\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)"
                ]
                
                for loc_pattern in full_loc_patterns:
                    loc_match = re.search(loc_pattern, text, re.IGNORECASE)
                    if loc_match:
                        if len(loc_match.groups()) > 1:
                            # Combine multiple parts
                            location = f"Via {loc_match.group(1)} di {loc_match.group(2)}"
                        else:
                            location = loc_match.group(1).strip()
                        break
            
            # Look for activity in the document
            activity = ""
            activity_patterns = [
                r"[Hh]al\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)",
                r"(?:Undangan|Rapat|Meeting)\s+([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)"
            ]
            
            for act_pattern in activity_patterns:
                act_match = re.search(act_pattern, text, re.IGNORECASE)
                if act_match:
                    activity = act_match.group(1).strip()
                    break
            
            item = {
                "day": day,
                "date": date,
                "time": time,
                "location": location,
                "activity": activity
            }
            if item not in schedule_items:
                schedule_items.append(item)
    
    # === FORMAT 6: Direct Polri extraction ===
    # Direct pattern for Polri format
    direct_polri_pattern = r"hari\s*/?\s*tanggal\s*:?\s*([A-Za-z]+)\s*/\s*(\d{1,2}\s+[A-Za-z]+\s+\d{4})\s+pukul\s*:?\s*(\d{1,2}[.:]\d{2})\s*WIB\s*s\.?d\.?\s*selesai\s+tempat\s*:?\s*(.+?)\s+Hal\s*:?\s*(.+?)(?=\n|[.;]|$)"
    direct_polri_matches = re.findall(direct_polri_pattern, text, re.IGNORECASE | re.DOTALL)
    
    for match in direct_polri_matches:
        if len(match) >= 5:
            day = match[0].strip()
            date = match[1].strip()
            time = f"{match[2]} - selesai"
            location = match[3].strip()
            activity = match[4].strip()
            
            item = {
                "day": day,
                "date": date,
                "time": time,
                "location": location,
                "activity": activity
            }
            if item not in schedule_items:
                schedule_items.append(item)
    
    # === FORMAT 5: Polri specific format ===
    # Look for Polri letter patterns
    polri_pattern = r"hari\s*/?\s*tanggal\s*:?\s*([A-Za-z]+)\s*/\s*(\d{1,2}\s+[A-Za-z]+\s+\d{4}).*?pukul\s*:?\s*([A-Za-z0-9\s\.\-:]+?)(?:.*?tempat\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?))?(?:.*?Hal\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?))?(?=\n|[.;]|$)"
    polri_matches = re.findall(polri_pattern, text, re.IGNORECASE | re.DOTALL)
    
    for match in polri_matches:
        if len(match) >= 3:
            day = match[0].strip()
            date = match[1].strip()
            time = normalize_time(match[2].strip())
            location = match[3].strip() if len(match) > 3 and match[3] else ""
            activity = match[4].strip() if len(match) > 4 and match[4] else ""
            
            # If location is empty, try to find it in the text
            if not location:
                # Look for location patterns in the text
                loc_patterns = [
                    r"tempat\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)",
                    r"di\s+([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)",
                    r"Ruang\s+([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)",
                    r"Ruang\s+Command\s+Center\s+([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)"
                ]
                for loc_pattern in loc_patterns:
                    loc_match = re.search(loc_pattern, text, re.IGNORECASE)
                    if loc_match:
                        location = loc_match.group(1).strip()
                        break
                
                # If still empty, try to extract the full location
                if not location:
                    full_loc_match = re.search(r"Ruang\s+Command\s+Center\s+Div\s+TIK\s+lantai\s+\d+\s+Gedung\s+Presisi\s+\d+,\s+Jalan\s+Trunojoyo\s+\d+,\s+Kebayoran\s+Baru,\s+Jakarta\s+Selatan", text, re.IGNORECASE)
                    if full_loc_match:
                        location = full_loc_match.group(0).strip()
                
                # If still empty, try a simpler approach
                if not location:
                    # Look for text after "tempat:"
                    tempat_match = re.search(r"tempat\s*:?\s*(.+?)(?=\s+Hal|\n|[.;]|$)", text, re.IGNORECASE)
                    if tempat_match:
                        location = tempat_match.group(1).strip()
            
            item = {
                "day": day,
                "date": date,
                "time": time,
                "location": location,
                "activity": activity
            }
            if item not in schedule_items:
                schedule_items.append(item)
    
    # === FORMAT 4: Comprehensive letter pattern ===
    # Try to extract all fields from a letter block in one pattern
    comprehensive_patterns = [
        # Pattern for letters with structured format
        r"(?:Hari\s*/?\s*Tanggal\s*:?\s*)?([A-Za-z]+)\s*[,/]\s*(\d{1,2}\s+[A-Za-z]+\s+\d{4}).*?(?:Pukul|Pk|Waktu)\s*:?\s*([A-Za-z0-9\s\.\-:]+?)(?:.*?Tempat\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?))?(?:.*?Hal\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?))?(?=\n|[.;]|$)",
        # Alternative pattern for different letter structures
        r"(?:Hari\s*/?\s*Tanggal\s*:?\s*)?([A-Za-z]+)\s*[,/]\s*(\d{1,2}\s+[A-Za-z]+\s+\d{4}).*?(?:Pukul|Pk|Waktu)\s*:?\s*([A-Za-z0-9\s\.\-:]+?)(?:.*?di\s+([A-Za-z0-9\s,\/\-\.()]+?))?(?:.*?perihal\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?))?(?=\n|[.;]|$)"
    ]
    
    for pattern in comprehensive_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE | re.DOTALL)
        for match in matches:
            if len(match) >= 3:
                day = match[0].strip()
                date = match[1].strip()
                time = normalize_time(match[2].strip())
                location = match[3].strip() if len(match) > 3 and match[3] else ""
                activity = match[4].strip() if len(match) > 4 and match[4] else ""
                
                # If location or activity is empty, try to find them in the text
                if not location:
                    # Look for location patterns in the text
                    loc_patterns = [
                        r"[Tt]empat\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)",
                        r"di\s+([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)",
                        r"Via\s+([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)"
                    ]
                    for loc_pattern in loc_patterns:
                        loc_match = re.search(loc_pattern, text, re.IGNORECASE)
                        if loc_match:
                            location = loc_match.group(1).strip()
                            break
                
                if not activity:
                    # Look for activity patterns in the text
                    act_patterns = [
                        r"[Hh]al\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)",
                        r"(?:Undangan|Rapat|Meeting|kerja\s+sama)\s+([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)"
                    ]
                    for act_pattern in act_patterns:
                        act_match = re.search(act_pattern, text, re.IGNORECASE)
                        if act_match:
                            activity = act_match.group(1).strip()
                            break
                
                item = {
                    "day": day,
                    "date": date,
                    "time": time,
                    "location": location,
                    "activity": activity
                }
                if item not in schedule_items:
                    schedule_items.append(item)
    
    # === FORMAT 2: Formal letter format ===
    # Extract day and date combinations - multiple patterns for different formats
    day_date_patterns = [
        # Pattern 1: "Selasa/15 Juli 2025" or "Senin, 24 Maret 2025"
        r"(Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu)\s*[,/]*\s*(\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4})",
        # Pattern 2: "hari/tanggal: Selasa/15 Juli 2025"
        r"hari\s*/?\s*tanggal\s*:?[\s-]*?(Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu)\s*/\s*(\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4})",
        # Pattern 3: "Hari/Tanggal: Senin, 24 Maret 2025"
        r"[Hh]ari\s*/?\s*[Tt]anggal\s*:?[\s-]*?(Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu)\s*[,/]\s*(\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4})",
        # Pattern 4: Just date without day
        r"(\d{1,2}\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember)\s+\d{4})"
    ]
    
    day_date_matches = []
    for pattern in day_date_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE)
        for match in matches:
            if isinstance(match, tuple):
                if len(match) == 2:
                    day_date_matches.append(match)
                else:
                    # For pattern 4 (date only), add empty day
                    day_date_matches.append(("", match[0]))
            else:
                # Single match, treat as date only
                day_date_matches.append(("", match))
        if day_date_matches:
            break  # Use first pattern that finds matches
    
    # Extract time patterns - multiple formats
    time_patterns = [
        # Pattern 1: "Pk. 08.30 WIB - Selesai" (prioritize this)
        r"(?:Pk|Pukul|Jam)\s*\.?\s*(\d{1,2}[.:]\d{2})\s*WIB\s*[\-–]\s*(?:Selesai|selesai|(\d{1,2}[.:]\d{2})\s*WIB)",
        # Pattern 2: "pukul: 10.00 WIB s.d. selesai"
        r"(?:pukul|pk|pkl|jam|waktu)\s*:?[\s-]*?(\d{1,2}[.:]\d{2})\s*WIB\s*(?:s\.?d\.?|sampai|hingga)\s*(?:selesai|(\d{1,2}[.:]\d{2})\s*WIB)",
        # Pattern 3: "10.00 - 12.00 WIB"
        r"(\d{1,2}[.:]\d{2})\s*[\-–]\s*(\d{1,2}[.:]\d{2})\s*WIB",
        # Pattern 4: "08:30 WIB"
        r"(\d{1,2}[.:]\d{2})\s*WIB",
        # Pattern 5: "pukul 15:00 sampai selesai"
        r"(?:pukul|pk|pkl|jam)\s+(\d{1,2}[.:]\d{2})\s*(?:sampai|hingga|s\.?d\.?)\s*(?:selesai|(\d{1,2}[.:]\d{2}))"
    ]
    
    time_matches = []
    for pattern in time_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE)
        for match in matches:
            if isinstance(match, tuple):
                if len(match) == 2 and match[1]:  # Both start and end time
                    time_matches.append(f"{match[0]} - {match[1]}")
                else:  # Only start time or "selesai"
                    if match[0]:
                        time_matches.append(f"{match[0]} - selesai")
                    else:
                        time_matches.append(f"{match[1]} - selesai")
            else:
                time_matches.append(match)
        if time_matches:
            break  # Use first pattern that finds matches
    
    # Extract location patterns - multiple formats
    location_patterns = [
        # Pattern 1: "tempat: Ruang Command Center"
        r"(?:tempat|lokasi|bertempat\s+di|ruang|ruangan)\s*:?[\s-]*?([A-Za-z0-9\s,\/\-\.()]+?)(?=\n|[.;]|$)",
        # Pattern 2: "di Ruang Command Center"
        r"di\s+([A-Za-z0-9\s,\/\-\.()]+?)(?:\s+(?:pada|tanggal)|[.;]|\n|$)",
        # Pattern 3: "Via Zoom Meeting di Command Center"
        r"(?:Via|Melalui)\s+([A-Za-z0-9\s,\/\-\.()]+?)(?:\s+di\s+([A-Za-z0-9\s,\/\-\.()]+?))?(?=[.;]|\n|$)",
        # Pattern 4: "Command Center/Media Center"
        r"([A-Za-z0-9\s,\/\-\.()]+?(?:Center|Center\/[A-Za-z0-9\s,\/\-\.()]+?))(?=[.;]|\n|$)",
        # Pattern 5: "Via Zoom Meeting di Command Center/Media Center satker masing-masing"
        r"Via\s+([A-Za-z0-9\s,\/\-\.()]+?)\s+di\s+([A-Za-z0-9\s,\/\-\.()\/]+?)(?=\s+satker|[.;]|\n|$)",
        # Pattern 6: "Via Zoom Meeting" (just the platform)
        r"Via\s+([A-Za-z0-9\s,\/\-\.()]+?)(?=\s+di|\s+Meeting|[.;]|\n|$)",
        # Pattern 7: "Zoom Meeting" (without Via)
        r"([A-Za-z0-9\s,\/\-\.()]+?\s+Meeting)(?=\s+di|[.;]|\n|$)"
    ]
    
    location_matches = []
    for pattern in location_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE)
        for match in matches:
            if isinstance(match, tuple):
                # Combine multiple parts
                clean_location = " ".join([part.strip() for part in match if part.strip()])
            else:
                clean_location = match.strip().rstrip('.,;')
            
            if clean_location and len(clean_location) > 2:
                location_matches.append(clean_location)
        if location_matches:
            break  # Use first pattern that finds matches
    
    # Extract activity patterns - multiple formats
    activity_patterns = [
        # Pattern 1: "Hal: Undangan Rapat Koordinasi"
        r"[Hh]al\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?)(?=[.;]|\n|$)",
        # Pattern 2: "perihal: kerja sama layanan"
        r"(?:perihal|tentang|acara|kegiatan)\s*:?\s*([A-Za-z0-9\s,\/\-\.()]+?)(?=[.;]|\n|$)",
        # Pattern 3: "Undangan Rapat Koordinasi"
        r"(?:Undangan|Rapat|Meeting|Kegiatan|Acara)\s+([A-Za-z0-9\s,\/\-\.()]+?)(?=[.;]|\n|$)",
        # Pattern 4: "kerja sama layanan bantuan polisi 110"
        r"([A-Za-z0-9\s,\/\-\.()]+?(?:kerja\s+sama|layanan|bantuan|polisi|koordinasi)[A-Za-z0-9\s,\/\-\.()]*?)(?=[.;]|\n|$)"
    ]
    
    activity_matches = []
    for pattern in activity_patterns:
        matches = re.findall(pattern, text, re.IGNORECASE)
        for match in matches:
            clean_activity = match.strip().rstrip('.,;')
            if clean_activity and len(clean_activity) > 3:
                activity_matches.append(clean_activity)
        if activity_matches:
            break  # Use first pattern that finds matches
    
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
    
    # Remove duplicates and keep only the best quality items
    unique_items = []
    for item in schedule_items:
        # Skip items with very incomplete data
        if not item["day"] and not item["date"]:
            continue
        if not item["time"] or item["time"] in ["Pk", "P", "1", "10"]:
            continue
        if not item["location"] and not item["activity"]:
            continue
            
        # Check if this item is already in unique_items
        is_duplicate = False
        for existing in unique_items:
            # Check for exact duplicates (same day, date, time, location, activity)
            if (existing["day"] == item["day"] and 
                existing["date"] == item["date"] and
                existing["time"] == item["time"] and
                existing["location"] == item["location"] and
                existing["activity"] == item["activity"]):
                is_duplicate = True
                break
            
            # Check for similar activities on same date/time (likely duplicates)
            if (existing["day"] == item["day"] and 
                existing["date"] == item["date"] and
                existing["time"] == item["time"] and
                (existing["activity"] in item["activity"] or 
                 item["activity"] in existing["activity"])):
                is_duplicate = True
                # Keep the one with more complete data
                if (len(item["location"]) > len(existing["location"]) or
                    len(item["activity"]) > len(existing["activity"])):
                    unique_items.remove(existing)
                    unique_items.append(item)
                break
        
        if not is_duplicate:
            unique_items.append(item)
    
    # Update schedule_items with deduplicated list
    schedule_items = unique_items
    
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
    
    # Handle "Pk. 08.30 WIB - Selesai" format
    if "Pk" in time_str or "Pukul" in time_str:
        # Extract time after Pk/Pukul
        time_match = re.search(r"(?:Pk|Pukul)\s*\.?\s*(\d{1,2}[.:]\d{2})", time_str, re.IGNORECASE)
        if time_match:
            base_time = time_match.group(1)
            # Check if it ends with "selesai"
            if "selesai" in time_str.lower():
                return f"{base_time} - selesai"
            else:
                # Look for end time
                end_match = re.search(r"[\-–]\s*(\d{1,2}[.:]\d{2})", time_str)
                if end_match:
                    return f"{base_time} - {end_match.group(1)}"
                else:
                    return f"{base_time} - selesai"
    
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