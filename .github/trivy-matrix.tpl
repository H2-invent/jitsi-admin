{{ range . -}}
{{ if (gt (len .Vulnerabilities) 0) -}}
{{ range .Vulnerabilities -}}
[{{ if eq .Severity "CRITICAL" }}🔴{{ else }}🟠{{ end }} {{ .Severity }}] {{ .PkgName }} {{ .InstalledVersion }} → {{ .FixedVersion | default "kein Fix" }} ({{ .VulnerabilityID }})
{{ end -}}
{{ end -}}
{{ end -}}
