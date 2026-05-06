import zipfile
import xml.etree.ElementTree as ET

def extract_text_from_docx(docx_path):
    try:
        with zipfile.ZipFile(docx_path) as z:
            xml_content = z.read('word/document.xml')
        
        tree = ET.fromstring(xml_content)
        
        # The namespace for Word processing XML
        ns = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
        
        text = []
        for paragraph in tree.findall('.//w:p', ns):
            para_text = []
            for run in paragraph.findall('.//w:r', ns):
                t = run.find('w:t', ns)
                if t is not None and t.text:
                    para_text.append(t.text)
            if para_text:
                text.append(''.join(para_text))
        
        return '\n'.join(text)
    except Exception as e:
        return str(e)

if __name__ == '__main__':
    content = extract_text_from_docx('Aura_Clinic_Current_Version_Detailed_Documentation.docx')
    with open('doc_output.txt', 'w', encoding='utf-8') as f:
        f.write(content)
