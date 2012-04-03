;------------------------------------
; dir2web3 config file
;------------------------------------

[global]
; set the refresh seed
seed = "1"

; set here the theme name (a folder with the same name must be present in the "system/themes" folder
theme = "quarantanum"
home_theme_file = "index.html"
default_theme_file = "index.html"

site_name = "Test"

[page]
; possible order values are: 
;"0-9" or "9-0" => priority (ascending or descending)
;"a-z" or "z-a" => alphabetical (ascending or descending)
;"t1-t2" or "t2-t1" => time of creation of the file (ascending or descending)

; here you can set the default order for the object in each page
objects_order = "0-9"

; here you can set the default order for the pages in the menu
pages_order = "0-9"

[text]
; set wrap of the text
txt_wrap = 600

;text that exceed this value will be truncated
max_txt_length = 1000000


[image]
; set the width and height of the thumbnails
thumb_max_x = 370
thumb_max_y = 300
;
show_info = no
show_caption = yes
; 
auto_thumb = yes

[download]
show_info = yes
