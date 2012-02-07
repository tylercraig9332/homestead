# spec file autogenerated by ess-pkg-utils

%define name hms
%define version 0.4.50
%define release 1
%define install_dir /var/www/hms

Summary: Housing Management System
Name:    %{name}
Version: %{version}
Release: %{release}
License: GPL
Group:   Development/PHP
URL:     http://phpwebsite.appstate.edu
Source0: %{name}.tar.bz2
Source1: phpwebsite-hms-latest.tar.bz2
Requires: php >= 5.0.0, php-gd >= 5.0.0

%description
The Housing Management System

%prep
%setup -T -n hms -c hms -a 0
%setup -T -D -b 1 -n hms

%post
/sbin/service httpd restart

%install
cd '%{_builddir}'
mkdir -p "$RPM_BUILD_ROOT%{install_dir}"
# phpWebSite and HMS are very tightly coupled, so included the perscribed version of phpWebSite.
mv phpwebsite-hms/* "$RPM_BUILD_ROOT%{install_dir}/"
mv phpwebsite-hms/.htaccess "$RPM_BUILD_ROOT%{install_dir}/"

# Install HMS under phpWebSite
mkdir -p "$RPM_BUILD_ROOT%{install_dir}/mod/hms/"
cp -r hms/* "$RPM_BUILD_ROOT%{install_dir}/mod/hms/"

# Install HMS Cosign script to phpWebSite
mv "$RPM_BUILD_ROOT%{install_dir}/mod/hms/inc/cosign.php"\
   "$RPM_BUILD_ROOT%{install_dir}/mod/users/scripts/hms-cosign.php"

# Clean up crap from the repo tht doesn't need to be in production
rm -Rf "$RPM_BUILD_ROOT%{install_dir}/mod/hms/util"
rm -f "$RPM_BUILD_ROOT%{install_dir}/mod/hms/inc/shs0001.wsdl"
rm -f "$RPM_BUILD_ROOT%{install_dir}/hmd/hms/inc/shs0001.wsdl.testing"
rm -f "$RPM_BUILD_ROOT%{install_dir}/mod/hms/build.xml"
rm -f "$RPM_BUILD_ROOT%{install_dir}/mod/hms/hms.spec"

# Install the production Banner WSDL file
mv "$RPM_BUILD_ROOT%{install_dir}/mod/hms/inc/shs0001.wsdl.prod"\
   "$RPM_BUILD_ROOT%{install_dir}/mod/hms/inc/shs0001.wsdl"

# Install the cron job
mkdir -p "$RPM_BUILD_ROOT/etc/cron.d"
mv "$RPM_BUILD_ROOT%{install_dir}/mod/hms/inc/hms-cron"\
   "$RPM_BUILD_ROOT/etc/cron.d/hms-cron"

# Create directory for HMS Archived Reports
mkdir "$RPM_BUILD_ROOT%{install_dir}/files/hms_reports"

# Put the PDF generator in the right place
mkdir -p "$RPM_BUILD_ROOT/opt"
mv "$RPM_BUILD_ROOT%{install_dir}/mod/hms/inc/wkhtmltopdf-i386"\
   "$RPM_BUILD_ROOT/opt/wkhtmltopdf-i386"

%clean
rm -rf "$RPM_BUILD_ROOT%install_dir"

%files
%defattr(-,apache,apache)
%{install_dir}
%defattr(-,root,root)
/etc/cron.d/hms-cron
%attr(0755,-,-) /opt/wkhtmltopdf-i386

%changelog
* Fri Oct 21 2011 Jeff Tickle <jtickle@tux.appstate.edu>
- Made the phpwebsite install more robust, including the theme
- Added Cron Job, but never tested it so it probably won't work
* Thu Jun  2 2011 Jeff Tickle <jtickle@tux.appstate.edu>
- Added build.xml and hms.spec to the repository, prevented these files from installing
- Added some comments
* Thu Apr 21 2011 Jeff Tickle <jtickle@tux.appstate.edu>
- New spec file for HMS, includes phpWebSite
