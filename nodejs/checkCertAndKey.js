import fs from 'fs';

export function checkFileContains(path, string) {
    const exist = fs.existsSync(path);
    if (!exist){
        return  false;
    }
    const contents = fs.readFileSync(path, 'utf-8');
    const result = contents.includes(string);
    return result;
}