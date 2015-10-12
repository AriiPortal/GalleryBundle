Galerie
=======

Publication de photos dans le portail.

Installation dans Symfony
-------------------------

### Enregistrer le module
Fichier: __app/AppKernel.php__

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Arii\GalleryBundle\AriiGalleryBundle(),
    }

### Ajouter le routage

Fichier: __app/config/routing.yml__

    # =======================================
    # Gallery
    # ----------------------------------------
    arii_Gallery:
        resource: "@AriiGalleryBundle/Resources/config/routing.yml"
        prefix:   /gallery/{_locale}
        requirements:
            _locale: en|fr|es|de|cn|ar|ru|jp 

