import os
from datetime import datetime

def save_as_markdown(content: str, output_dir: str = "output") -> str:
    os.makedirs(output_dir, exist_ok=True)
    filename = f"doc_{datetime.now().strftime('%Y%m%d_%H%M%S')}.md"
    filepath = os.path.join(output_dir, filename)
    
    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content.strip())
        
    return filepath