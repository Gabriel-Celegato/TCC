<?php
class Validator {
    
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public function validatePassword($password) {
        return strlen($password) >= 6 && 
               preg_match('/[A-Z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }
    
    public function validateName($name) {
        return strlen($name) >= 3 && 
               strlen($name) <= 100 && 
               preg_match('/^[A-Za-zÀ-ÿ\s]+$/', $name);
    }
    
    public function validatePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 11;
    }
    
    public function validateCep($cep) {
        $cep = preg_replace('/[^0-9]/', '', $cep);
        return strlen($cep) == 8;
    }
    
    public function validateUf($uf) {
        $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 
                'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 
                'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
        return in_array(strtoupper($uf), $ufs);
    }
    
    public function validateBirthDate($date) {
        $data = DateTime::createFromFormat('Y-m-d', $date);
        if (!$data) return false;
        
        $hoje = new DateTime();
        $idade = $hoje->diff($data)->y;
        
        return $idade >= 16 && $idade <= 120;
    }
    
    public function isEmpty($value) {
        return empty(trim($value));
    }
    
    public function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }
}
?>