# Media processing dependencies

Install the following packages on Debian/Ubuntu servers to enable image and video optimization:

```bash
sudo apt-get update
sudo apt-get install -y ffmpeg
sudo apt-get install -y imagemagick php-imagick || sudo apt-get install -y php-gd
```

Optional optimizers (if you want extra lossless compression):

```bash
sudo apt-get install -y jpegoptim optipng pngquant gifsicle
```

Verify PHP extensions:

```bash
php -m | grep -E 'imagick|gd'
```

FFmpeg and ffprobe should also be available on the PATH:

```bash
ffmpeg -version
ffprobe -version
```
